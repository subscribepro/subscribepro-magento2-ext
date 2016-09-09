<?php

namespace Swarming\SubscribePro\Observer\Checkout;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class IsAllowedGuest implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Helper\Quote $quoteHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper
    ) {
        $this->generalConfig = $generalConfig;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\DataObject $result */
        $result = $observer->getData('result');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');

        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->generalConfig->isEnabled($websiteCode)) {
            return;
        }

        if ($this->quoteHelper->hasSubscription($quote)) {
            $result->setIsAllowed(false);
        }
    }
}
