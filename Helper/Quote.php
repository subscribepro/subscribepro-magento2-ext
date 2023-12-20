<?php

namespace Swarming\SubscribePro\Helper;

use Magento\Quote\Api\Data\CartInterface;

class Quote
{
    /**
     * @var QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param QuoteItem $quoteItemHelper
     */
    public function __construct(
        QuoteItem $quoteItemHelper
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * @param CartInterface $quote
     * @return bool
     */
    public function hasSubscription($quote)
    {
        return !empty($this->getSubscriptionItems($quote));
    }

    /**
     * @param CartInterface $quote
     * @return array
     */
    public function getSubscriptionItems($quote)
    {
        $subscriptions = [];

        /** @var \Magento\Quote\Model\Quote $quote */
        $items = $quote->getItemsCollection();
        foreach ($items as $item) {
            if ($this->quoteItemHelper->hasSubscription($item)) {
                $subscriptions[] = $item;
            }
        }

        return $subscriptions;
    }

    /**
     * Whether or not the quote has been built by Subscribe Pro's recurring order placement system
     * Depends on the item_added_by_subscribe_pro attribute on the line item.
     *
     * @param CartInterface $quote
     * @return bool
     */
    public function isRecurringQuote($quote)
    {
        $subscriptions = $this->getSubscriptionItems($quote);
        if (empty($subscriptions)) {
            return false;
        }

        foreach ($subscriptions as $subscription) {
            if ($this->quoteItemHelper->getItemAddedBySubscribePro($subscription)) {
                return true;
            }
        }

        return false;
    }
}
