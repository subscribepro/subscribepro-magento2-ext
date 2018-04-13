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
     * Validate Customer First Order Rule Condition
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $subscriptionOptions = $this->getSubscriptionOptions($model);

        if ($this->subscriptionOptionsAreFalse($subscriptionOptions)) {
            return parent::validate($model);
        }

        // Check quote item attributes
        if ($subscriptionOptions['new_subscription'] || $subscriptionOptions['is_fulfilling']) {
            return $this->validateAttribute($subscriptionOptions['interval']);
        } else {
            return false;
        }
    }
}