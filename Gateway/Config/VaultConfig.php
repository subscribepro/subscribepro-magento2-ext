<?php

namespace Swarming\SubscribePro\Gateway\Config;

class VaultConfig extends \Magento\Payment\Gateway\Config\Config
{
    public const KEY_ACTIVE = 'active';

    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }
}
