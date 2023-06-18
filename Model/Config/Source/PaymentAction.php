<?php

namespace Swarming\SubscribePro\Model\Config\Source;

class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    public const AUTHORIZE = 'authorize';
    public const AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * @return string[]
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
