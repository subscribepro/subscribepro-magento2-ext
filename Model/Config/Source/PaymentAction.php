<?php

namespace Swarming\SubscribePro\Model\Config\Source;

class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    const AUTHORIZE = 'authorize';
    const AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => self::AUTHORIZE_CAPTURE,
                'label' => __('Purchase'),
            ]
        ];
    }
}
