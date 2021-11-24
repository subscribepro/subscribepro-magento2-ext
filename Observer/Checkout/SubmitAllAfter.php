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
     * @var \Swarming\SubscribePro\Model\Order\DetailsCreator
     */
    private $orderDetailsCreator;

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
     * @param \Swarming\SubscribePro\Model\Order\DetailsCreator $orderDetailsCreator
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator,
        \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor,
        \Psr\Log\LoggerInterface $logger,
        \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig,
        \Swarming\SubscribePro\Model\Order\DetailsCreator $orderDetailsCreator
    ) {
        $this->generalConfig = $generalConfig;
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionCreator = $subscriptionCreator;
        $this->cartItemOptionProcessor = $cartItemOptionProcessor;
        $this->logger = $logger;
        $this->thirdPartyPaymentConfig = $thirdPartyPaymentConfig;
        $this->orderDetailsCreator = $orderDetailsCreator;
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
            $this->checkoutSession->setData(
                SubscriptionCreator::CREATED_SUBSCRIPTION_IDS,
                $result[SubscriptionCreator::CREATED_SUBSCRIPTION_IDS]
            );
            $this->checkoutSession->setData(
                SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT,
                $result[SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->checkoutSession->setData(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, []);
            $this->checkoutSession->setData(SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, 0);
        }

        // store order details in SubscribePro. This should not impact order success or customer experience
        try {
            $this->processOrderDetails($order, $result);
        } catch (\Exception $e) {
            $this->logger->critical($e);
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
            || $this->isThirdPartyPaymentMethodAllowed($methodCode, $storeId);
    }

    /**
     * @param string $methodCode
     * @return bool
     */
    private function isPaymentMethodAllowed(string $methodCode): bool
    {
        return in_array($methodCode, $this->builtInMethods, true);
    }

    /**
     * @param string $methodCode
     * @param int $storeId
     * @return bool
     */
    private function isThirdPartyPaymentMethodAllowed(string $methodCode, int $storeId): bool
    {
        return $methodCode === $this->thirdPartyPaymentConfig->getAllowedMethod($storeId);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param array $result
     * @return void
     */
    private function processOrderDetails(OrderInterface $order, array $result): void
    {
        if (empty($result[SubscriptionCreator::CREATED_SUBSCRIPTION_IDS])) {
            return;
        }
        $this->orderDetailsCreator->createOrderDetails($order);
    }
}
