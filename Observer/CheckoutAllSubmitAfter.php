<?php

namespace Swarming\SubscribePro\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class CheckoutAllSubmitAfter implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $configGeneral;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionCreator
     */
    protected $subscriptionCreator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configGeneral = $configGeneral;
        $this->subscriptionCreator = $subscriptionCreator;
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

        $websiteId = $quote->getStore()->getWebsite()->getStoreId();
        if (!$this->configGeneral->isEnabled($websiteId)) {
            return;
        }

        try {
            $this->subscriptionCreator->createSubscriptions($quote);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
