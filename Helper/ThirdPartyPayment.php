<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Helper;

class ThirdPartyPayment
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\ThirdPartyPayment
     */
    private $thirdPartyPaymentConfig;

    /**
     * @param \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
    ) {
        $this->thirdPartyPaymentConfig = $thirdPartyPaymentConfig;
    }

    /**
     * @param string $methodCode
     * @param int $storeId
     * @return bool
     */
    public function isThirdPartyPaymentMethodAllowed(string $methodCode, int $storeId): bool
    {
        return in_array($methodCode, $this->thirdPartyPaymentConfig->getAllowedMethods($storeId), true);
    }
}
