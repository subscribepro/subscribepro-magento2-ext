<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

class Product extends \Magento\SalesRule\Model\Rule\Condition\Product
{
    const SUBSCRIPTION_STATUS_ANY = 0;
    const SUBSCRIPTION_STATUS_NEW = 1;
    const SUBSCRIPTION_STATUS_REORDER = 2;

    /**
     * Add special attributes
     *
     * @param array $attributes
     * @return void
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $attributes['quote_item_part_of_subscription'] = __('Subscription - Status');
        $attributes['quote_item_subscription_interval'] = __('Subscription - Current Interval');
        $attributes['quote_item_subscription_reorder_ordinal'] = __('Subscription - Re-order Ordinal');
    }

    /**
     * Validate Product Rule Condition
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        if ($model->getProductOption()
            && $model->getProductOption()->getExtensionAttributes()
            && $model->getProductOption()->getExtensionAttributes()->getSubscriptionOption()
        ) {
            $subscriptionOptions = $model->getProductOption()->getExtensionAttributes()->getSubscriptionOption();
            $subscriptionInterval = $subscriptionOptions->getInterval();
            $subscriptionFulfilling = $subscriptionOptions->getIsFulfilling();
            $subscriptionReorderOrdinal = $subscriptionOptions->getReorderOrdinal();
            $itemCreatesNewSubscription = !$subscriptionFulfilling;
        } else {
            return false;
        }

        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                // Check quote item attributes
                // Get value set on rule condition
                $conditionValue = $this->getValueParsed();
                // Get operator set on rule condition
                $op = $this->getOperatorForValidate();
                // Handle different status types
                switch ($conditionValue) {
                    case self::SUBSCRIPTION_STATUS_ANY:
                        $matchResult = true;
                        break;
                    case self::SUBSCRIPTION_STATUS_NEW:
                        $matchResult = $itemCreatesNewSubscription;
                        break;
                    case self::SUBSCRIPTION_STATUS_REORDER:
                        $matchResult = $subscriptionFulfilling;
                        break;
                    default:
                        $matchResult = false;
                        break;
                }
                // Only == or != operators supported
                // In case of !=, do invert $matchResult
                if($op != '==') {
                    $matchResult = !$matchResult;
                }
                // Return our result
                return $matchResult;
            case 'quote_item_subscription_interval':
                // Check quote item attributes
                if ($itemCreatesNewSubscription) {
                    // This is a new subscription
                    return parent::validateAttribute(0);
                }
                else if ($subscriptionFulfilling) {
                    // This is a recurring order on a subscription
                    return parent::validateAttribute($subscriptionInterval);
                }
                else {
                    return false;
                }
            case 'quote_item_subscription_reorder_ordinal':
                // Check quote item attributes
                if ($itemCreatesNewSubscription) {
                    // This is a new subscription
                    return parent::validateAttribute(0);
                }
                else if ($subscriptionFulfilling) {
                    // This is a recurring order on a subscription
                    return parent::validateAttribute($subscriptionReorderOrdinal);
                }
                else {
                    return false;
                }
            default:
                return parent::validate($model);
        }
    }
    /**
     * Retrieve input type
     *
     * @return string
     */
    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                return 'select';
            case 'quote_item_subscription_interval':
                return 'string';
            case 'quote_item_subscription_reorder_ordinal':
                return 'string';
            default:
                return parent::getInputType();
        }
    }
    /**
     * Retrieve value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                return 'select';
            case 'quote_item_subscription_interval':
                return 'text';
            case 'quote_item_subscription_reorder_ordinal':
                return 'text';
            default:
                return parent::getValueElementType();
        }
    }

    public function getValueSelectOptions()
    {
        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                return array(
                    array('value' => self::SUBSCRIPTION_STATUS_ANY, 'label' => __('Part of Subscription (New or Re-order)')),
                    array('value' => self::SUBSCRIPTION_STATUS_NEW, 'label' => __('Part of New Subscription Order')),
                    array('value' => self::SUBSCRIPTION_STATUS_REORDER, 'label' => __('Part of Subscription Re-order')),
                );
            default:
                return parent::getValueSelectOptions();
        }
    }
}
