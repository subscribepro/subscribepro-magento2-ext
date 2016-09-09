<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class SubscriptionDiscount extends General
{
    /**
     * @param string|null $store
     * @return bool
     */
    public function isApplyDiscountToCatalogPrice($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/subscription_discount/apply_discount_to_catalog_price',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param string|null $store
     * @return string
     */
    public function getCartRuleCombineType($store = null)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/subscription_discount/cartrule_combine_type',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param string|null $store
     * @return string
     */
    public function getDiscountMessage($store = null)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/subscription_discount/discount_message',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
