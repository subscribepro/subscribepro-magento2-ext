<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Quote\Payment;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\Address\AddressInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as SubscribeProConfigProvider;

class GetPaymentProfileId
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    private $platformPaymentProfileService;

    /**
     * @var \Swarming\SubscribePro\Service\Payment\PaymentProfileDataBuilderInterface
     */
    private $paymentProfileDataBuilder;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Swarming\SubscribePro\Service\Payment\PaymentProfileDataBuilderInterface $paymentProfileDataBuilder
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Swarming\SubscribePro\Service\Payment\PaymentProfileDataBuilderInterface $paymentProfileDataBuilder
    ) {
        $this->tokenManagement = $tokenManagement;
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        $this->paymentProfileDataBuilder = $paymentProfileDataBuilder;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @param int $platformCustomerId
     * @return string
     * @throws \Exception
     */
    public function execute(OrderPaymentInterface $payment, int $platformCustomerId): string
    {
        $paymentToken = $this->tokenManagement->getByPaymentId($payment->getEntityId());
        if (!$paymentToken || !$paymentToken->getIsActive()) {
            throw new \UnexpectedValueException('The vault is not found.');
        }

        return $paymentToken->getPaymentMethodCode() === SubscribeProConfigProvider::CODE
            ? $paymentToken->getGatewayToken()
            : $this->getExternalProfileId($paymentToken, $payment->getOrder(), $platformCustomerId);
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @param \Magento\Sales\Model\Order $order
     * @param int $platformCustomerId
     * @return string
     */
    private function getExternalProfileId(
        PaymentTokenInterface $paymentToken,
        \Magento\Sales\Model\Order $order,
        int $platformCustomerId
    ): string {

        try {
            $platformPaymentProfiles = $this->platformPaymentProfileService->loadProfiles(
                [PaymentProfileInterface::PAYMENT_TOKEN => $paymentToken->getGatewayToken()]
            );
            $platformPaymentProfile = $platformPaymentProfiles[0] ?? null;
        } catch (\Exception $e) {
            $platformPaymentProfile = null;
        }

        $platformPaymentProfile = $platformPaymentProfile
            ?: $this->createExternalPaymentProfile($paymentToken, $order, $platformCustomerId);

        return (string)$platformPaymentProfile->getId();
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @param \Magento\Sales\Model\Order $order
     * @param int $platformCustomerId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    private function createExternalPaymentProfile(
        PaymentTokenInterface $paymentToken,
        \Magento\Sales\Model\Order $order,
        int $platformCustomerId
    ): PaymentProfileInterface {

        $paymentProfileData = $this->paymentProfileDataBuilder->build($platformCustomerId, $paymentToken);

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress instanceof OrderAddressInterface) {
            $paymentProfileData[PaymentProfileInterface::BILLING_ADDRESS] = $this->getBillingAddressData(
                $billingAddress
            );
        }

        $platformPaymentProfile = $this->platformPaymentProfileService->createExternalVaultProfile($paymentProfileData);
        $this->platformPaymentProfileService->saveProfile($platformPaymentProfile);

        return $platformPaymentProfile;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $billingAddress
     * @return array
     */
    private function getBillingAddressData(OrderAddressInterface $billingAddress): array
    {
        return [
            AddressInterface::FIRST_NAME => $billingAddress->getFirstname(),
            AddressInterface::LAST_NAME => $billingAddress->getLastname(),
            AddressInterface::COMPANY => $billingAddress->getCompany(),
            AddressInterface::STREET1 => $billingAddress->getStreetLine(1),
            AddressInterface::STREET2 => $billingAddress->getStreetLine(2),
            AddressInterface::STREET3 => $billingAddress->getStreetLine(3),
            AddressInterface::CITY => $billingAddress->getCity(),
            AddressInterface::REGION => $billingAddress->getRegionCode(),
            AddressInterface::POSTCODE => $billingAddress->getPostcode(),
            AddressInterface::COUNTRY => $billingAddress->getCountryId(),
            AddressInterface::PHONE => $billingAddress->getTelephone()
        ];
    }
}
