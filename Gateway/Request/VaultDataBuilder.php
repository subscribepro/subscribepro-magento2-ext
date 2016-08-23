<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Exception;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class VaultDataBuilder implements BuilderInterface
{
    const PAYMENT_PROFILE_ID = 'profile_id';

    /**
     * @param array $buildSubject
     * @return array
     * @throws Exception
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        $extensionAttributes = $payment->getExtensionAttributes();

        if (!$extensionAttributes || !$extensionAttributes->getVaultPaymentToken()) {
            throw new Exception('The vault is not found.');
        }

        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        return [
            self::PAYMENT_PROFILE_ID => $paymentToken->getGatewayToken()
        ];
    }
}
