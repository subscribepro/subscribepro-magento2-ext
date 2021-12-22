<?php

namespace Swarming\SubscribePro\Helper;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Magento\Framework\App\ObjectManager;
use Swarming\SubscribePro\Model\Config\Source\ThreeDsType;

class Vault
{
    const STATE_PENDING = 'pending';

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \Swarming\SubscribePro\Helper\PaymentProfileThreeDs
     */
    protected $paymentProfileThreeDs;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactory;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
     * @param \Swarming\SubscribePro\Helper\PaymentProfileThreeDs|null $paymentProfileThreeDs
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory,
        \Swarming\SubscribePro\Helper\PaymentProfileThreeDs $paymentProfileThreeDs = null
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->encryptor = $encryptor;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->paymentProfileThreeDs = $paymentProfileThreeDs
            ?: ObjectManager::getInstance()->get(PaymentProfileThreeDs::class);
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

        $tokenDetails = $this->getTokenDetails(
            $profile->getCreditcardType(),
            $profile->getCreditcardLastDigits(),
            $profile->getCreditcardMonth(),
            $profile->getCreditcardYear(),
            $profile->getPaymentToken()
        );

        if ($this->gatewayConfig->isThreeDSActive()
            && $this->gatewayConfig->getThreeDsType() === ThreeDsType::GATEWAY_INDEPENDENT
            && !$this->paymentProfileThreeDs->isThreeDsAuthenticated($profile)
        ) {
            $tokenDetails = $this->markPendingTokenDetails($tokenDetails);
        }

        $token->setTokenDetails($tokenDetails);
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

        unset($tokenDetails['state']);
        if ($this->gatewayConfig->isThreeDSActive()
            && $this->gatewayConfig->getThreeDsType() === ThreeDsType::GATEWAY_INDEPENDENT
            && !$this->paymentProfileThreeDs->isThreeDsAuthenticated($profile)
        ) {
            $tokenDetails['state'] = self::STATE_PENDING;
        }

        $token->setTokenDetails($this->encodeDetails($tokenDetails));
        $token->setExpiresAt($this->getExpirationDate($profile->getCreditcardYear(), $profile->getCreditcardMonth()));
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
     * @param string $tokenDetails
     * @return string
     */
    public function markPendingTokenDetails($tokenDetails)
    {
        $tokenDetailsData = $this->decodeDetails($tokenDetails);
        $tokenDetailsData['state'] = self::STATE_PENDING;
        return $this->encodeDetails($tokenDetailsData);
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
