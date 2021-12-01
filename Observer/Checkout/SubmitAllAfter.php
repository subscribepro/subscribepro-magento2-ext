<?php

namespace Swarming\SubscribePro\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterface;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;

class SubmitAllAfter implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionCreator
     */
    protected $subscriptionCreator;

    /**
     * @var \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor
     */
    protected $cartItemOptionProcessor;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Swarming\SubscribePro\Model\Config\ThirdPartyPayment
     */
    private $thirdPartyPaymentConfig;

    /**
     * @var \Swarming\SubscribePro\Helper\ThirdPartyPayment
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
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator
     * @param \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
     * @param \Swarming\SubscribePro\Helper\ThirdPartyPayment $thirdPartyPayment
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator,
        \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor,
        \Psr\Log\LoggerInterface $logger,
        \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig,
        \Swarming\SubscribePro\Helper\ThirdPartyPayment $thirdPartyPayment
    ) {
        $this->generalConfig = $generalConfig;
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionCreator = $subscriptionCreator;
        $this->cartItemOptionProcessor = $cartItemOptionProcessor;
        $this->logger = $logger;
        $this->thirdPartyPaymentConfig = $thirdPartyPaymentConfig;
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
            $this->checkoutSession->setData(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, $result[SubscriptionCreator::CREATED_SUBSCRIPTION_IDS]);
            $this->checkoutSession->setData(SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, $result[SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT]);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->checkoutSession->setData(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, []);
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
