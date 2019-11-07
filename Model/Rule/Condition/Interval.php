<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

/**
 * Class Status
 * @package Swarming\SubscribePro\Model\Rule\Condition
 */
class Interval extends Base
{
    /**
     * Load attribute options
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $this->setAttributeOption([
            'quote_item_subscription_interval' => __('Subscription - Interval'),
        ]);
        return $this;
    }

    /**
     * Validate Interval Condition
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
            && ($interval = $this->validateAttribute($this->getInterval($model)))
        ? $interval
        : false;
    }
}