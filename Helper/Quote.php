<?php

namespace Swarming\SubscribePro\Helper;

class Quote
{
    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function hasSubscription($quote)
    {
        return !empty($this->getSubscriptionItems($quote));
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function getSubscriptionItems($quote)
    {
        $subscriptions = [];

        $items = $quote->getItemsCollection(false);
        foreach ($items as $item) {
            if ($this->quoteItemHelper->hasSubscription($item)) {
                $subscriptions[] = $item;
            }
        }

        return $subscriptions;
    }
}
