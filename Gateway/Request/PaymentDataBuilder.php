<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class PaymentDataBuilder implements BuilderInterface
{
    const PAYMENT_METHOD_TOKEN = 'payment_method_token';

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        return [
            self::PAYMENT_METHOD_TOKEN => $payment->getAdditionalInformation(self::PAYMENT_METHOD_TOKEN),
            VaultConfigProvider::IS_ACTIVE_CODE => $payment->getAdditionalInformation(
                VaultConfigProvider::IS_ACTIVE_CODE
            )
        ];
    }
}
