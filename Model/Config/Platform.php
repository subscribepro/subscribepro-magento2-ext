<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class Platform extends General
{
    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getClientId($websiteCode = null)
    {
        return $this->scopeConfig->getValue('swarming_subscribepro/platform/client_id', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getClientSecret($websiteCode = null)
    {
        return $this->scopeConfig->getValue('swarming_subscribepro/platform/client_secret', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isLogEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag('swarming_subscribepro/platform/log_enabled', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getLogLevel($websiteCode = null)
    {
        return $this->scopeConfig->getValue('swarming_subscribepro/platform/log_level', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getLogFilename($websiteCode = null)
    {
        return $this->scopeConfig->getValue('swarming_subscribepro/platform/log_filename', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }
}
