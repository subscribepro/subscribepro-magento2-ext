<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Helper\ApplePay;

use Magento\Quote\Api\Data\AddressInterface;
use SubscribePro\Service\Token\TokenInterface;
use Swarming\SubscribePro\Platform\Service\Token as PlatformServiceToken;

class Vault
{
    /**
     * @var PlatformServiceToken
     */
    private PlatformServiceToken $platformServiceToken;

    /**
     * Construct Vault.
     *
     * @param PlatformServiceToken $token
     */
    public function __construct(
        PlatformServiceToken $token
    ) {
        $this->platformServiceToken = $token;
    }

    /**
     * @param AddressInterface $billingAddress
     * @param array $applePayPaymentData
     * @return TokenInterface
     */
    public function createApplePayPaymentToken(
        AddressInterface $billingAddress,
        array $applePayPaymentData
    ): TokenInterface {
        // Build request data
        /** @var \SubscribePro\Service\Address\Address $billingAddress */
        $requestData = [
            'billing_address' => [
                'first_name' => $billingAddress->getData('firstname'),
                'last_name' => $billingAddress->getData('lastname'),
            ],
            'applepay_payment_data' => $applePayPaymentData,
        ];
        // Add optional fields - billing address
        $optionalFields = [
            'company' => 'company',
            'city' => 'city',
            'postcode' => 'postcode',
            'country' => 'country_id',
            'phone' => 'telephone'
        ];
        foreach ($optionalFields as $fieldKey => $magentoFieldKey) {
            $addressValue = $billingAddress->getData($magentoFieldKey);
            if ($addressValue && strlen($addressValue)) {
                $requestData['billing_address'][$fieldKey] = $addressValue;
            }
        }
        if ($billingAddress->getStreet1() && strlen($billingAddress->getStreet1())) {
            $requestData['billing_address']['street1'] = $billingAddress->getStreet1();
        }
        if ($billingAddress->getStreet2() && strlen($billingAddress->getStreet2())) {
            $requestData['billing_address']['street2'] = $billingAddress->getStreet2();
        }
        if ($billingAddress->getStreet3() && strlen($billingAddress->getStreet3())) {
            $requestData['billing_address']['street3'] = $billingAddress->getStreet3();
        }
        if ($billingAddress->getRegionCode() && strlen($billingAddress->getRegionCode())) {
            $requestData['billing_address']['region'] = $billingAddress->getRegionCode();
        }

        // Create token
        $token = $this->platformServiceToken->createToken($requestData);
        $token = $this->platformServiceToken->saveToken($token);

        return $token;
    }
}
