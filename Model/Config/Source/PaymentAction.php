<?php

namespace Swarming\SubscribePro\Model\Config\Source;

class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'authorize',
                'label' => __('Authorize'),
            ],
            [
                'value' => 'authorize_capture',
                'label' => __('Purchase'),
            ]
        ];
    }
}
