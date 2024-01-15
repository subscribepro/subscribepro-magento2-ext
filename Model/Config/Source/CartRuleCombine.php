<?php

namespace Swarming\SubscribePro\Model\Config\Source;

class CartRuleCombine implements \Magento\Framework\Option\ArrayInterface
{
    public const TYPE_COMBINE_SUBSCRIPTION = 'combine';
    public const TYPE_APPLY_GREATEST = 'greatest';
    public const TYPE_APPLY_LEAST = 'least';
    public const TYPE_APPLY_CART_DISCOUNT = 'cart';
    public const TYPE_APPLY_SUBSCRIPTION = 'subscription';

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::TYPE_COMBINE_SUBSCRIPTION,
                'label'=> __('Combine Subscription Discount With Other Discounts')
            ],
            [
                'value' => self::TYPE_APPLY_GREATEST,
                'label' => __('Apply Greatest Discount')],
            [
                'value' => self::TYPE_APPLY_LEAST,
                'label' => __('Apply Least Discount')
            ],
            [
                'value' => self::TYPE_APPLY_CART_DISCOUNT,
                'label' => __('Always Apply Cart Rule Discount')
            ],
            [
                'value' => self::TYPE_APPLY_SUBSCRIPTION,
                'label' => __('Always Apply Subscription Discount')
            ]
        ];
    }
}
