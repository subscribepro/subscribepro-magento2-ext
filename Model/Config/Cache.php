<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class Cache extends General
{
    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getCacheLifeTime($websiteCode = null)
    {
        return $this->scopeConfig->getValue('swarming_subscribepro/advanced/cache_lifetime', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }
}
