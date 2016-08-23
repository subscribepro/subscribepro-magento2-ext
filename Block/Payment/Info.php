<?php

namespace Swarming\SubscribePro\Block\Payment;

use Magento\Payment\Block\ConfigurableInfo;

class Info extends ConfigurableInfo
{
    /**
     * @param string $field
     * @return \Magento\Framework\Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }
}
