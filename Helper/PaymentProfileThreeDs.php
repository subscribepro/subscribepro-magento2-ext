<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Helper;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class PaymentProfileThreeDs
{
    /**
     * @var array
     */
    private $activeThreeDsStatuses = [
        PaymentProfileInterface::THREE_DS_PENDING_AUTHENTICATION,
        PaymentProfileInterface::THREE_DS_AUTHENTICATED,
        PaymentProfileInterface::THREE_DS_AUTHENTICATION_FAILED
    ];

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $token
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return void
     */
    public function processThreeDsStatus(PaymentTokenInterface $token, PaymentProfileInterface $profile)
    {
        if (!$this->hasThreeDsStatus($profile)) {
            throw new \InvalidArgumentException('The payment profile does not have 3DS status.');
        }

        if ($this->isThreeDsFailed($profile)) {
            $token->setIsActive(false);
            $token->setIsVisible(false);
        } else {
            $token->setIsVisible($this->isThreeDsAuthenticated($profile));
        }
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return bool
     */
    public function hasThreeDsStatus(PaymentProfileInterface $profile)
    {
        return in_array($profile->getThreeDsStatus(), $this->activeThreeDsStatuses, true);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return bool
     */
    public function isThreeDsFailed(PaymentProfileInterface $profile)
    {
        return $profile->getThreeDsStatus() === PaymentProfileInterface::THREE_DS_AUTHENTICATION_FAILED;
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return bool
     */
    public function isThreeDsAuthenticated(PaymentProfileInterface $profile)
    {
        return $profile->getThreeDsStatus() === PaymentProfileInterface::THREE_DS_AUTHENTICATED;
    }
}
