<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

/**
 * Class Product
 * @package Swarming\SubscribePro\Model\Rule\Condition
 * @method getAttribute()
 */

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
        $subscriptionOptions = $this->getSubscriptionOptions($model);
        if ($subscriptionOptions === null) {
            return parent::validate($model);
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
                        $matchResult = ($subscriptionOptions->getCreatesNewSubscription() || $subscriptionOptions->getIsFulfilling());
                        break;
                    case self::SUBSCRIPTION_STATUS_NEW:
                        $matchResult = $subscriptionOptions->getCreatesNewSubscription();
                        break;
                    case self::SUBSCRIPTION_STATUS_REORDER:
                        $matchResult = $subscriptionOptions->getIsFulfilling();
                        break;
                    default:
                        $matchResult = false;
                        break;
                }
                // Since this attribute is a select list only == and != operators are allowed
                // In case of !=, do invert $matchResult
                if($op != '==') {
                    $matchResult = !$matchResult;
                }
                // Return our result
                return $matchResult;
            case 'quote_item_subscription_interval':
                // Check quote item attributes
                if ($subscriptionOptions->getCreatesNewSubscription() || $subscriptionOptions->getIsFulfilling()) {
                    return parent::validateAttribute($subscriptionOptions->getInterval());
                } else {
                    return false;
                }
            case 'quote_item_subscription_reorder_ordinal':
                // Check quote item attributes
                if ($subscriptionOptions->getCreatesNewSubscription()) {
                    // This is a new subscription
                    return $this->validateAttribute(0);
                }
                else if ($subscriptionOptions->getIsFulfilling()) {
                    // This is a recurring order on a subscription
                    return $this->validateAttribute($subscriptionOptions->getReorderOrdinal());
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

    /**
     * Retrieve the select options for the subscription status
     *
     * @return array
     */
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

    /**
     * Helper that retrieves the subscription options associated with the quote
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface|null
     */
    protected function getSubscriptionOptions(\Magento\Framework\Model\AbstractModel $model)
    {
        if ($model instanceof \Magento\Quote\Model\Quote\Item\Interceptor
            && $model->getProductOption()
            && $model->getProductOption()->getExtensionAttributes()
            && $model->getProductOption()->getExtensionAttributes()->getSubscriptionOption()
        ) {
            return $model->getProductOption()->getExtensionAttributes()->getSubscriptionOption();
        }
        return null;
    }
}
