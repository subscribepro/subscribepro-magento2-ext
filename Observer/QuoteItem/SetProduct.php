<?php

namespace Swarming\SubscribePro\Observer\QuoteItem;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetProduct implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Psr\Log\LoggerInterface $logger,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
        $this->generalConfig = $generalConfig;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->generalConfig->isEnabled()) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote\Item */
        $item = $observer->getEvent()->getData('quote_item');
        $itemFulfilsSubscriptions = $this->quoteItemHelper->isItemFulfilsSubscription($item);
        $fixedPrice = $this->quoteItemHelper->getFixedPrice($item);
        if ($itemFulfilsSubscriptions && $fixedPrice) {
            $item = ($item->getParentItem() ? $item->getParentItem() : $item);
            $item->setCustomPrice($fixedPrice);
            $item->setOriginalCustomPrice($fixedPrice);
            $item->getProduct()->setIsSuperMode(true);
        }
    }
}
