<?php

namespace Swarming\SubscribePro\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;

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
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator
     * @param \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator,
        \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->generalConfig = $generalConfig;
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionCreator = $subscriptionCreator;
        $this->cartItemOptionProcessor = $cartItemOptionProcessor;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        $this->addProductOptionsToQuoteItems($quote);

        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getData('order');

        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->generalConfig->isEnabled($websiteCode)
            || $order->getPayment()->getMethod() != GatewayConfigProvider::CODE
            || $order->getPayment()->getMethod() != ApplePayConfigProvider::CODE
            || !$quote->getCustomerId()
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
}
