<?php

namespace Swarming\SubscribePro\Plugin\SalesRule;

use Magento\SalesRule\Model\Rule\Condition\Product\Combine as ProductCombineRule;

class Conditions
{
    /**
     * @param \Magento\SalesRule\Model\Rule\Condition\Product\Combine $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetNewChildSelectOptions(ProductCombineRule $subject, callable $proceed)
    {
        $conditions = $proceed();

        foreach ($conditions as &$condition) {
            if ($condition['label'] == __('Cart Item Attribute')) {
                $condition['value'] = array_merge_recursive($condition['value'], $this->getSubscriptionConditions());
            }
        }

        return $conditions;
    }

    /**
     * @return array
     */
    protected function getSubscriptionConditions()
    {
        return [
            [
                'label' => __('Subscription - Status'),
                'value' => \Swarming\SubscribePro\Model\Rule\Condition\Status::class,
            ],

            [
                'label' => __('Subscription - Interval'),
                'value' => \Swarming\SubscribePro\Model\Rule\Condition\Interval::class,
            ],

            [
                'label' => __('Subscription - Re-order Ordinal'),
                'value' => \Swarming\SubscribePro\Model\Rule\Condition\ReorderOrdinal::class,
            ],
        ];
    }
}
