<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Config;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;

class ApplePayConfigProvider extends GatewayConfigProvider
{
    const CODE = 'subscribe_pro_apple_pay';
    const VAULT_CODE = 'subscribe_pro_vault';

    /**
     * @param null $websiteCode
     * @return bool
     */
    public function isEnabledPayment($websiteCode = null): bool
    {
        return ($this->generalConfig->isEnabled($websiteCode) && $this->generalConfig->isApplePayEnabled($websiteCode));
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getDomain($storeId = null): string
    {
        $value = $this->gatewayConfig->getValue('domain', $storeId);

        return ($value !== null) ? $value : '';
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isActiveNonSubscription($storeId = null): bool
    {
        return (bool) $this->gatewayConfig->getValue('active_non_subscription', $storeId);
    }

    /**
     * @param null $websiteCode
     * @return string
     */
    public function getApiBaseUrl($websiteCode = null): string
    {
        $value = $this->generalConfig->getBaseUrl($websiteCode);

        return ($value)?? '';
    }
}
