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
        $interval = $this->discountRuleHelper->validateInterval($this->quoteItemHelper->getSubscriptionParams($model));
        return false === $interval ? false : $this->validateAttribute($interval);
    }
}
