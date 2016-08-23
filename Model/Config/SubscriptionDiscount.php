<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class SubscriptionDiscount extends General
{
    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function doApplyDiscountToCatalogPrice($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/subscription_discount/apply_discount_to_catalog_price',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function getCartRuleCombineType($websiteCode = null)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/subscription_discount/cartrule_combine_type',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }
}
