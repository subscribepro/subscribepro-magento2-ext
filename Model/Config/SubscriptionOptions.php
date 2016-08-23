<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class SubscriptionOptions extends General
{
    /**
     * @param string $store
     * @return bool
     */
    public function isAllowedCoupon($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/subscription_options/allow_coupon',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
