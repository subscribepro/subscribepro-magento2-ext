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
        // If the subscription parameters are not given
        // or if the item is not a new or recurring subscription order
        // or if there is no valid interval set, then return false;
        // otherwise, return the interval
        return !$this->subscriptionOptionsAreFalse($model)
            && $this->isItemNewOrFulfillingSubscription($model)
            && ($reorder_ordinal = $this->validateAttribute($this->getReorderOrdinal($model)))
        ? $reorder_ordinal
        : false;
    }
}