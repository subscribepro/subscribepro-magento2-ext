<?php

namespace Swarming\SubscribePro\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\ManagerInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;

class SubmitBefore implements ObserverInterface
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
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Swarming\SubscribePro\Helper\Quote $quoteHelper
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator,
        \Psr\Log\LoggerInterface $logger,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->generalConfig = $generalConfig;
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionCreator = $subscriptionCreator;
        $this->logger = $logger;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->quoteHelper = $quoteHelper;
        $this->eventManager = $eventManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');

        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->generalConfig->isEnabled($websiteCode)) {
            return;
        }

        // Make sure the subscription is a recurring subscription order
        $subscriptionItems = $this->quoteHelper->getSubscriptionItems($quote);
        if (empty($subscriptionItems)) {
            return;
        }

        $recurringOrder = false;
        foreach ($subscriptionItems as $subscriptionItem) {
            // A subscription ID will only exist on a quote item before an order if the subscription already exists
            // This means that if the quote item has a subscription ID it must be a reorder
            if ($this->quoteItemHelper->getSubscriptionId($subscriptionItem)) {
                $recurringOrder = true;
                break;
            }
        }

        if (!$recurringOrder) {
            return;
        }

        $this->eventManager->dispatch(
            'subscribe_pro_before_subscription_reorder_place',
            [
                'quote_id' => $quote->getId(),
                'quote' => $quote,
            ]
        );
    }
}
