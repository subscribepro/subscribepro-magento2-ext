<?php

namespace Swarming\SubscribePro\Model\Config\Source;

class CcType extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return ['AE', 'VI', 'MC', 'DI', 'JCB', 'DN', 'OT'];
    }
}
