<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Payment;

use Magento\Payment\Block\ConfigurableInfo;

class ApplePayInfo extends ConfigurableInfo
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
