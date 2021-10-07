<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service\Payment;

use Magento\Vault\Api\Data\PaymentTokenInterface;

interface PaymentProfileDataBuilderInterface
{
    /**
     * @param int $platformCustomerId
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @return array
     */
    public function build(int $platformCustomerId, PaymentTokenInterface $paymentToken): array;
}
