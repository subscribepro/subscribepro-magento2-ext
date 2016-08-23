<?php

namespace Swarming\SubscribePro\Helper;

use Swarming\SubscribePro\Model\Quote\ItemOptionsManager;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class QuoteItem
{
    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function hasSubscription($quote)
    {
        $hasSubscription = false;
        $items = (array)$quote->getItems();
        foreach ($items as $item) {
            if ($this->isSubscriptionEnabled($item) || $this->isFulfilsSubscription($item)) {
                $hasSubscription = true;
            }
        }
        return $hasSubscription;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    public function isSubscriptionEnabled(AbstractItem $item)
    {
        $createSubscriptionOption = $item->getOptionByCode(ItemOptionsManager::SUBSCRIPTION_CREATING);
        return $createSubscriptionOption && $createSubscriptionOption->getValue();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    public function isFulfilsSubscription(AbstractItem $item)
    {
        $buyRequest = $item->getOptionByCode('info_buyRequest');
        $buyRequest = $buyRequest ? unserialize($buyRequest->getValue()) : [];
        return isset($buyRequest['options']['is_fulfils_subscription']) ? $buyRequest['options']['is_fulfils_subscription'] : false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool|string
     */
    public function getSubscriptionInterval(AbstractItem $item)
    {
        $intervalSubscriptionOption = $item->getOptionByCode(ItemOptionsManager::SUBSCRIPTION_INTERVAL);
        return $intervalSubscriptionOption ? $intervalSubscriptionOption->getValue() : null;
    }
}
