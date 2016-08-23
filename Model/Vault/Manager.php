<?php

namespace Swarming\SubscribePro\Model\Vault;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class Manager
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $config
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $config,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $token
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function initVault(PaymentTokenInterface $token, PaymentProfileInterface $profile)
    {
        $token->setPaymentMethodCode(ConfigProvider::CODE);
        $token->setGatewayToken($profile->getId());
        $token->setIsActive(true);
        $token->setIsVisible(true);
        $token->setCustomerId($profile->getMagentoCustomerId());
        $token->setTokenDetails($this->getTokenDetails($profile));
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

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return string
     */
    protected function getTokenDetails(PaymentProfileInterface $profile)
    {
        $tokenDetails = [
            'type' => $this->config->getMappedCcType($profile->getCreditcardType()),
            'maskedCC' => $profile->getCreditcardLastDigits(),
            'expirationDate' => $profile->getCreditcardMonth() . '/' . $profile->getCreditcardYear()
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
    protected function getExpirationDate($year, $month)
    {
        $expDate = new \DateTime($year . '-' . $month . '-01 00:00:00', new \DateTimeZone('UTC'));
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
