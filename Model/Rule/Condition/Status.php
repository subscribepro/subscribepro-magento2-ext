<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

/**
 * Class Status
 * @package Swarming\SubscribePro\Model\Rule\Condition
 */
class Status extends Base
{
    /**
     * Load attribute options
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $attributes = [
            'quote_item_part_of_subscription' => __('Subscription - Status'),

        ];
        $this->setAttributeOption($attributes);
        return $this;
    }

    /**
     * Get input type
     * @return string
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * Get value element type
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * Get value select options
     * @return array|mixed
     */
    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            $this->setData(
                'value_select_options',
                [
                    ['value' => self::SUBSCRIPTION_STATUS_ANY, 'label' => __('Part of Subscription (New or Re-order)')],
                    ['value' => self::SUBSCRIPTION_STATUS_NEW, 'label' => __('Part of New Subscription Order')],
                    ['value' => self::SUBSCRIPTION_STATUS_REORDER, 'label' => __('Part of Subscription Re-order')],
                ]
            );
        }
        return $this->getData('value_select_options');
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
        // Get value set on rule condition
        $conditionValue = $this->getValueParsed();
        // Get operator set on rule condition
        $op = $this->getOperatorForValidate();

        // Handle different status types
        switch ($conditionValue) {
            case self::SUBSCRIPTION_STATUS_ANY:
                $matchResult = ($subscriptionOptions['new_subscription'] || $subscriptionOptions['is_fulfilling']);
                break;
            case self::SUBSCRIPTION_STATUS_NEW:
                $matchResult = $subscriptionOptions['new_subscription'];
                break;
            case self::SUBSCRIPTION_STATUS_REORDER:
                $matchResult = $subscriptionOptions['is_fulfilling'];
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
    }
}