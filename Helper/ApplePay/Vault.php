<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Helper\ApplePay;

use Magento\Quote\Api\Data\AddressInterface;
use \Swarming\SubscribePro\Platform\Service\Token as PlatformServiceToken;

class Vault
{
    /**
     * @var PlatformServiceToken
     */
    private $platformServiceToken;

    public function __construct(
        PlatformServiceToken $token
    ) {
        $this->platformServiceToken = $token;
    }

    /**
     * @param AddressInterface $billingAddress
     * @param array $applePayPaymentData
     * @return string
     */
    public function createApplePayPaymentToken(AddressInterface $billingAddress, array $applePayPaymentData): string
    {
        // Build request data
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
            if (strlen($billingAddress->getData($magentoFieldKey))) {
                $requestData['billing_address'][$fieldKey] = $billingAddress->getData($magentoFieldKey);
            }
        }
        if (strlen($billingAddress->getStreet1())) {
            $requestData['billing_address']['street1'] = $billingAddress->getStreet1();
        }
        if (strlen($billingAddress->getStreet2())) {
            $requestData['billing_address']['street2'] = $billingAddress->getStreet2();
        }
        if (strlen($billingAddress->getRegionCode())) {
            $requestData['billing_address']['region'] = $billingAddress->getRegionCode();
        }

        // Create token
        $token = $this->platformServiceToken->createToken($requestData);
        $token = $this->platformServiceToken->saveToken($token);

        return $token;
    }
}
