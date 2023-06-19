<?php

namespace Swarming\SubscribePro\Model\Config\Source;

class ThreeDsType implements \Magento\Framework\Option\ArrayInterface
{
    public const GATEWAY_INDEPENDENT = 'gateway_independent';
    public const GATEWAY_SPECIFIC = 'gateway_specific';

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::GATEWAY_INDEPENDENT,
                'label' => __('Gateway Independent'),
            ],
            [
                'value' => self::GATEWAY_SPECIFIC,
                'label' => __('Gateway Specific'),
            ]
        ];
    }
}
