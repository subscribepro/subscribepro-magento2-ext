<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Config;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;

class ApplePayConfigProvider extends GatewayConfigProvider
{
    public const CODE = 'subscribe_pro_apple_pay';
    public const VAULT_CODE = 'subscribe_pro_vault';

    /**
     * @param string|null $websiteId
     * @return bool
     */
    public function isEnabledPayment($websiteId = null): bool
    {
        return ($this->generalConfig->isEnabled($websiteId) && $this->generalConfig->isApplePayEnabled($websiteId));
    }

    /**
     * @param string|null $storeId
     * @return string
     */
    public function getDomain($storeId = null): string
    {
        $value = $this->gatewayConfig->getValue('domain', $storeId);

        return ($value !== null) ? $value : '';
    }

    /**
     * @param string|null $storeId
     * @return bool
     */
    public function isActiveNonSubscription($storeId = null): bool
    {
        return (bool) $this->gatewayConfig->getValue('active_non_subscription', $storeId);
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getApiBaseUrl($websiteCode = null): string
    {
        $value = $this->generalConfig->getBaseUrl($websiteCode);

        return $value ?: '';
    }
}
