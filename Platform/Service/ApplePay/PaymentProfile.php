<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Platform\Service\ApplePay;

use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Platform\Service\AbstractService;

/**
 * @method \SubscribePro\Service\PaymentProfile\PaymentProfileService getService($websiteId = null)
 */
class PaymentProfile extends AbstractService
{
    /**
     * @param array $paymentProfileData
     * @param int|null $websiteId
     * @return PaymentProfileInterface
     */
    public function createApplePayProfile(array $paymentProfileData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createApplePayProfile($paymentProfileData);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveProfile(PaymentProfileInterface $paymentProfile, $websiteId = null)
    {
        return $this->getService($websiteId)->saveProfile($paymentProfile);
    }
}
