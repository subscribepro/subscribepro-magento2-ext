<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class Advanced extends General
{
    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getCacheLifeTime($websiteCode = null)
    {
        return $this->scopeConfig->getValue('swarming_subscribepro/advanced/cache_lifetime', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }

    /**
     * @param string|null $websiteCode
     * @return string[]
     */
    public function getWebhookIpAddresses($websiteCode = null)
    {
        $ipAddresses = $this->scopeConfig->getValue('swarming_subscribepro/advanced/webhook_ipaddresses', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        return $this->explodeIps($ipAddresses);
    }

    /**
     * @param $ipAddress
     * @param string|null $websiteCode
     * @return bool
     */
    public function isWebhookIpAllowed($ipAddress, $websiteCode = null)
    {
        return in_array($ipAddress, $this->getWebhookIpAddresses($websiteCode));
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isDebuggingEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag('swarming_subscribepro/advanced/enable_debugging', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }

    /**
     * @param string $ips
     * @return string[]
     */
    protected function explodeIps($ips)
    {
        return empty($ips) ? [] : explode(',', str_replace(' ', '', $ips));
    }
}
