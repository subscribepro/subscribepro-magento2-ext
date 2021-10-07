<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service\Payment;

use Magento\Vault\Api\Data\PaymentTokenInterface;

class PaymentProfileDataBuilderPool implements PaymentProfileDataBuilderInterface
{
    /**
     * @var \Swarming\SubscribePro\Service\Payment\PaymentProfileDataBuilderInterface[]
     */
    private $paymentProfileDataBuilders;

    /**
     * @param \Swarming\SubscribePro\Service\Payment\PaymentProfileDataBuilderInterface[] $paymentProfileDataBuilders
     */
    public function __construct(array $paymentProfileDataBuilders = [])
    {
        foreach ($paymentProfileDataBuilders as $methodCode => $paymentProfileDataBuilder) {
            if (!$paymentProfileDataBuilder instanceof PaymentProfileDataBuilderInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s payment profile data builder must implement %s interface',
                        $methodCode,
                        PaymentProfileDataBuilderInterface::class
                    )
                );
            }
        }
        $this->paymentProfileDataBuilders = $paymentProfileDataBuilders;
    }

    /**
     * @param int $platformCustomerId
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @return array
     */
    public function build(int $platformCustomerId, PaymentTokenInterface $paymentToken): array
    {
        $paymentProfileDataBuilder = $this->paymentProfileDataBuilders[$paymentToken->getPaymentMethodCode()] ?? null;
        if (!$paymentProfileDataBuilder) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Payment profile data builder is not defined for %s payment method',
                    $paymentToken->getPaymentMethodCode()
                )
            );
        }
        return $paymentProfileDataBuilder->build($platformCustomerId, $paymentToken);
    }
}
