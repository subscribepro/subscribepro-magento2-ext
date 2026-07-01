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

    /**
     * Total API request timeout (seconds) for SP API calls made in a frontend context.
     *
     * @param string|null $websiteCode
     * @return int
     */
    public function getApiRequestTimeout($websiteCode = null)
    {
        return (int) $this->scopeConfig->getValue(
            'swarming_subscribepro/advanced/api_request_timeout',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * Connect timeout (seconds) for SP API calls. 0 disables the separate connect timeout.
     *
     * @param string|null $websiteCode
     * @return int
     */
    public function getApiConnectTimeout($websiteCode = null)
    {
        return (int) $this->scopeConfig->getValue(
            'swarming_subscribepro/advanced/api_connect_timeout',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * Get the secret key which should be used for authenticating incoming webhook.
     *
     * @param string|null $websiteCode
     *
     * @return mixed
     */
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
     * @return bool
     */
    public function isExpiredCardsEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/advanced/enable_account_expires',
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
