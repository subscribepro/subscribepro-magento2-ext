<?php

namespace Swarming\SubscribePro\Helper;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Sdk;

class Vault
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactory;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Token
     */
    private $token;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory,
        \Swarming\SubscribePro\Platform\Service\Token $token
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->encryptor = $encryptor;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->token = $token;
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface                $token
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @param string                                                       $paymentMethodCode
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function initVault(
        PaymentTokenInterface $token,
        PaymentProfileInterface $profile,
        string $paymentMethodCode = ConfigProvider::CODE
    ) {
        $token->setPaymentMethodCode($paymentMethodCode);
        $token->setGatewayToken($profile->getId());
        $token->setIsActive(true);
        $token->setIsVisible(true);
        $token->setCustomerId($profile->getMagentoCustomerId());
        $token->setTokenDetails($this->getTokenDetails(
            $profile->getCreditcardType(),
            $profile->getCreditcardLastDigits(),
            $profile->getCreditcardMonth(),
            $profile->getCreditcardYear(),
            $profile->getPaymentToken()
        ));
        $token->setExpiresAt($this->getExpirationDate($profile->getCreditcardYear(), $profile->getCreditcardMonth()));
        $token->setPublicHash($this->generatePublicHash($token));
        return $token;
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $token
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function updateVault(PaymentTokenInterface $token, PaymentProfileInterface $profile)
    {
        $tokenDetails = $this->decodeDetails($token->getTokenDetails());
        $tokenDetails['expirationDate'] = $profile->getCreditcardMonth() . '/' . $profile->getCreditcardYear();
        $token->setTokenDetails($this->encodeDetails($tokenDetails));
        $token->setExpiresAt($this->getExpirationDate($profile->getCreditcardYear(), $profile->getCreditcardMonth()));
        return $token;
    }

    public function createApplePayPaymentToken($billingAddress, array $applePayPaymentData)
    {
        // Build request data
        $requestData = array(
            'billing_address' => [
                'first_name' => $billingAddress->getData('firstname'),
                'last_name' => $billingAddress->getData('lastname'),
            ],
            'applepay_payment_data' => $applePayPaymentData,
        );
        // Add optional fields - billing address
        $optionalFields = ['company' => 'company', 'city' => 'city', 'postcode' => 'postcode', 'country' => 'country_id', 'phone' => 'telephone', ];
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
        $token = $this->token->createToken($requestData);
        $token = $this->token->saveToken($token);

        return $token;
    }

    /**
     * @param string $cardType
     * @param string $lastDigits
     * @param string $month
     * @param string $year
     * @return string
     */
    public function getTokenDetails($cardType, $lastDigits, $month, $year, $paymentToken)
    {
        $tokenDetails = [
            'type' => $this->gatewayConfig->getMappedCcType($cardType),
            'maskedCC' => $lastDigits,
            'expirationDate' => $month . '/' . $year,
            'paymentToken' => $paymentToken,
        ];
        return $this->encodeDetails($tokenDetails);
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @return string
     */
    protected function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * @param string $year
     * @param string $month
     * @return string
     */
    public function getExpirationDate($year, $month)
    {
        $expDate = $this->dateTimeFactory->create(
            $year . '-' . $month . '-01 00:00:00',
            new \DateTimeZone('UTC')
        );
        $expDate->add(new \DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * @param string $details
     * @return array
     */
    protected function decodeDetails($details)
    {
        return json_decode($details ?: '{}', true);
    }

    /**
     * @param array $details
     * @return string
     */
    protected function encodeDetails(array $details)
    {
        return json_encode($details);
    }
}
