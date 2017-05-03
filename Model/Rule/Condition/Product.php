<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

class Product extends \Magento\SalesRule\Model\Rule\Condition\Product
{
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
        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                // Check quote item attributes
                $itemFulfilsSubscription = $model->getData('item_fulfils_subscription');
                $itemCreatesNewSubscription = $model->getData('create_new_subscription_at_checkout');
                // Get value set on rule condition
                $conditionValue = $this->getValueParsed();
                // Get operator set on rule condition
                $op = $this->getOperatorForValidate();
                // Handle different status types
                switch ($conditionValue) {
                    case self::SUBSCRIPTION_STATUS_ANY:
                        $matchResult = (bool) ($itemFulfilsSubscription || $itemCreatesNewSubscription);
                        break;
                    case self::SUBSCRIPTION_STATUS_NEW:
                        $matchResult = (bool) $itemCreatesNewSubscription;
                        break;
                    case self::SUBSCRIPTION_STATUS_REORDER:
                        $matchResult = (bool) ($itemFulfilsSubscription && !$itemCreatesNewSubscription);
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
                if ($model->getData('create_new_subscription_at_checkout') == '1') {
                    // This is a new subscription
                    return parent::validateAttribute($model->getData('new_subscription_interval'));
                }
                else if ($model->getData('item_fulfils_subscription') == '1') {
                    // This is a recurring order on a subscription
                    return parent::validateAttribute($model->getData('subscription_interval'));
                }
                else {
                    return false;
                }
            case 'quote_item_subscription_reorder_ordinal':
                // Check quote item attributes
                if ($model->getData('create_new_subscription_at_checkout') == '1') {
                    // This is a new subscription
                    $reorderOrdinal = 0;
                    return parent::validateAttribute($reorderOrdinal);
                }
                else if ($model->getData('item_fulfils_subscription') == '1') {
                    // This is a recurring order on a subscription
                    $reorderOrdinal = $model->getData('subscription_reorder_ordinal');
                    return parent::validateAttribute($reorderOrdinal);
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
