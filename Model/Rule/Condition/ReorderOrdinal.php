<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

/**
 * Class Status
 * @package Swarming\SubscribePro\Model\Rule\Condition
 */
class ReorderOrdinal extends Base
{
    /**
     * Load attribute options
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption([
            'quote_item_subscription_reorder_ordinal' => __('Subscription - Re-order Ordinal'),
        ]);
        return $this;
    }

    /**
     * Validate Reorder Ordinal Condition
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $reorder_ordinal = $this->discountRuleHelper->validateReorderOrdinal($this->quoteItemHelper->getSubscriptionParams($model));
        return false === $reorder_ordinal ? false : $this->validateAttribute($reorder_ordinal);
    }
}
