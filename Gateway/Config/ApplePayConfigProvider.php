<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Config;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider;

class ApplePayConfigProvider extends ConfigProvider
{
    const CODE = 'subscribe_pro_apple_pay';

    const VAULT_CODE = 'subscribe_pro_vault';

    public function isEnabledPayment($website)
    {
        return ($this->generalConfig->isEnabled($website) && $this->generalConfig->isApplePayEnabled($website));
    }

    public function getDomain($storeId = null)
    {
        return $this->gatewayConfig->getValue('domain', $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isActiveNonSubscription($storeId = null): bool
    {
        return (bool)$this->gatewayConfig->getValue('active_non_subscription', $storeId);
    }

    /**
     * @param null $websiteCode
     * @return string
     */
    public function getApiBaseUrl($websiteCode = null)
    {
        return $this->generalConfig->getBaseUrl($websiteCode);
    }
}
