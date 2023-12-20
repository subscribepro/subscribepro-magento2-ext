<?php

namespace Swarming\SubscribePro\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\PaymentAdditionalInformationProviderInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Swarming\SubscribePro\Platform\Service\PaymentProfile;

/**
 * Payment additional information provider that returns predefined value.
 *
 * @api
 */
class PaymentAdditionalInformationProvider implements PaymentAdditionalInformationProviderInterface
{
    /**
     * @var PaymentProfile
     */
    private $paymentProfileService;

    /**
     * PaymentAdditionalInformationProvider constructor.
     * @param PaymentProfile $paymentProfileService
     */
    public function __construct(PaymentProfile $paymentProfileService)
    {
        $this->paymentProfileService = $paymentProfileService;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalInformation(PaymentTokenInterface $paymentToken): array
    {
        $paymentProfileId = $paymentToken->getGatewayToken();
        $paymentProfile = $this->paymentProfileService->loadProfile($paymentProfileId);
        $paymentMethodToken = $paymentProfile->getPaymentToken();

        return [
            'payment_method_token' => $paymentMethodToken,
        ];
    }
}
