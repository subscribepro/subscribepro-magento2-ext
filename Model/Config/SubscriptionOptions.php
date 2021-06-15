<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class SubscriptionOptions extends General
{
    const QTY_MIN_DAYS_TO_NEXT_ORDER = 1;

    /**
     * @var \Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactory;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
    ) {
        parent::__construct($scopeConfig);
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * @param string|null $store
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

    /**
     * @param string|null $store
     * @return bool
     */
    public function isAllowedCancel($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/subscription_options/allow_cancel',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @return string
     */
    public function getEarliestDateForNextOrder()
    {
        return $this->dateTimeFactory
            ->create('+' . self::QTY_MIN_DAYS_TO_NEXT_ORDER . ' days')
            ->format('Y-m-d');
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getMySubscriptionsLoadCount($websiteCode = null)
    {
        return (int) $this->scopeConfig->getValue('swarming_subscribepro/subscription_options/my_subscriptions_count', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isChildSkuForConfigurableEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag('swarming_subscribepro/subscription_options/use_child_sku_when_configurable', ScopeInterface::SCOPE_WEBSITE, $websiteCode);
    }
}
