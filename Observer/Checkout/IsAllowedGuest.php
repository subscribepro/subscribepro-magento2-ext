<?php

namespace Swarming\SubscribePro\Observer\Checkout;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class IsAllowedGuest implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $configGeneral;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
    ) {
        $this->configGeneral = $configGeneral;
        $this->quoteItemHelper = $quoteItemHelper;
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
        if (!$this->configGeneral->isEnabled($websiteCode)) {
            return;
        }

        if ($this->quoteItemHelper->hasQuoteSubscription($quote)) {
            $result->setIsAllowed(false);
        }
    }
}
