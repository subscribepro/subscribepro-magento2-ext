<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class General
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag('swarming_subscribepro/general/enabled', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }
}
