<?php

namespace Swarming\SubscribePro\Model\Config\Source;

use Psr\Log\LogLevel;

class LogLevels implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => LogLevel::DEBUG, 'label' => __('Debug')],
            ['value' => LogLevel::INFO, 'label' => __('Info')],
            ['value' => LogLevel::NOTICE, 'label' => __('Notice')],
            ['value' => LogLevel::WARNING, 'label' => __('Warning')],
            ['value' => LogLevel::ERROR, 'label' => __('Error')],
            ['value' => LogLevel::CRITICAL, 'label' => __('Critical')],
            ['value' => LogLevel::ALERT, 'label' => __('Alert')],
            ['value' => LogLevel::EMERGENCY, 'label' => __('Emergency')],
        ];
    }
}
