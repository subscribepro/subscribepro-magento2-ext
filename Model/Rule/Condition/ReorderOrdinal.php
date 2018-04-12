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
     * Validate Customer First Order Rule Condition
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        if ($this->subscriptionOptionsAreFalse($model)) {
            return parent::validate($model);
        }
        // Check quote item attributes
        if ($subscriptionOptions['new_subscription'] || $subscriptionOptions['is_fulfilling']) {
            // This is a new subscription
            return $this->validateAttribute($subscriptionOptions['reorder_ordinal']);
        } else {
            return false;
        }
    }
}