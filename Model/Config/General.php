<?php

namespace Swarming\SubscribePro\Model\Config;

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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getBaseUrl($websiteCode = null)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/platform/base_url',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/general/enabled',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isApplePayEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag(
            'payment/subscribe_pro_apple_pay/active',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }
}
