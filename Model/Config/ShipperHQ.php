<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class ShipperHQ extends General
{
    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getRecurringOrderGroup($websiteCode = null)
    {
        return $this->scopeConfig->getValue('swarming_subscribepro/shipperhq/recurring_order_group', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getSubscriptionProductGroup($websiteCode = null)
    {
        return $this->scopeConfig->getValue('swarming_subscribepro/shipperhq/frontend_subscription_product_group', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }
}
