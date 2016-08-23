<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class PaymentDataBuilder implements BuilderInterface
{
    const PAYMENT_METHOD_TOKEN = 'payment_method_token';

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        return [
            self::PAYMENT_METHOD_TOKEN => $payment->getAdditionalInformation(self::PAYMENT_METHOD_TOKEN),
            VaultConfigProvider::IS_ACTIVE_CODE => $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)
        ];
    }
}
