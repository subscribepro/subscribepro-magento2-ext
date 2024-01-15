<?php

namespace Swarming\SubscribePro\Observer\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;
use Swarming\SubscribePro\Helper\ThirdPartyPayment;
use Swarming\SubscribePro\Model\Config\General;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;

class SubmitAllAfter implements ObserverInterface
{
    /**
     * @var General
     */
    protected $generalConfig;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var SubscriptionCreator
     */
    protected $subscriptionCreator;

    /**
     * @var CartItemOptionsProcessor
     */
    protected $cartItemOptionProcessor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ThirdPartyPayment
     */
    private $thirdPartyPayment;

    /**
     * @var array
     */
    private $builtInMethods = [
        GatewayConfigProvider::CODE,
        ApplePayConfigProvider::CODE
    ];

    /**
     * @param General $generalConfig
     * @param Session $checkoutSession
     * @param SubscriptionCreator $subscriptionCreator
     * @param CartItemOptionsProcessor $cartItemOptionProcessor
     * @param LoggerInterface $logger
     * @param ThirdPartyPayment $thirdPartyPayment
     */
    public function __construct(
        General                                         $generalConfig,
        Session                                         $checkoutSession,
        SubscriptionCreator                             $subscriptionCreator,
        CartItemOptionsProcessor                        $cartItemOptionProcessor,
        LoggerInterface                                 $logger,
        ThirdPartyPayment $thirdPartyPayment
    ) {
        $this->generalConfig = $generalConfig;
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionCreator = $subscriptionCreator;
        $this->cartItemOptionProcessor = $cartItemOptionProcessor;
        $this->logger = $logger;
        $this->thirdPartyPayment = $thirdPartyPayment;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        $this->addProductOptionsToQuoteItems($quote);

        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getData('order');

        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->generalConfig->isEnabled($websiteCode)
            || !$quote->getCustomerId()
            || !$this->canOrderBeProcessed($order)
        ) {
            return;
        }

        try {
            $result = $this->subscriptionCreator->createSubscriptions($quote, $order);
            /* @phpstan-ignore-next-line */
            $this->checkoutSession->setData(
                SubscriptionCreator::CREATED_SUBSCRIPTION_IDS,
                $result[SubscriptionCreator::CREATED_SUBSCRIPTION_IDS]
            );
            /* @phpstan-ignore-next-line */
            $this->checkoutSession->setData(
                SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT,
                $result[SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
            /* @phpstan-ignore-next-line */
            $this->checkoutSession->setData(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, []);
            /* @phpstan-ignore-next-line */
            $this->checkoutSession->setData(SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, 0);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    private function addProductOptionsToQuoteItems($quote)
    {
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getProductOption()) { // Skip if options are already added
                continue;
            }
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $item = $this->cartItemOptionProcessor->addProductOptions($quoteItem->getProductType(), $quoteItem);
            $this->cartItemOptionProcessor->applyCustomOptions($item);
        }
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     */
    private function canOrderBeProcessed(OrderInterface $order): bool
    {
        $storeId = (int)$order->getStoreId();
        $methodCode = $order->getPayment()->getMethod();

        return ($this->isPaymentMethodAllowed($methodCode) && $order->getState() !== Order::STATE_PAYMENT_REVIEW)
            || $this->thirdPartyPayment->isThirdPartyPaymentMethodAllowed($methodCode, $storeId);
    }

    /**
     * @param string $methodCode
     * @return bool
     */
    private function isPaymentMethodAllowed(string $methodCode): bool
    {
        return in_array($methodCode, $this->builtInMethods, true);
    }
}
