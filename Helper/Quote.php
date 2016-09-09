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
        $hasSubscription = false;
        $items = (array)$quote->getItems();
        foreach ($items as $item) {
            if ($this->quoteItemHelper->hasSubscription($item)) {
                $hasSubscription = true;
                break;
            }
        }
        return $hasSubscription;
    }
}
