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
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/advanced/cache_lifetime',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    public function getWebhookSecretKey($websiteCode = null)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/advanced/webhook_secret',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isDebuggingEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/advanced/enable_debugging',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isHostedMySubscriptionsPageEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/advanced/enable_hosted_my_subscriptions',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getHostedMySubscriptionWidgetConfig($websiteCode = null)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/advanced/custom_json_hosted_my_subscriptions',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getHostedMySubscriptionWidgetUrl($websiteCode = null)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/advanced/hosted_my_subscriptions_url',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }
}
