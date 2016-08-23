<?php

namespace Swarming\SubscribePro\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';

    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }
}
