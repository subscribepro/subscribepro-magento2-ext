<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Exception;
use Magento\Payment\Gateway\Request\BuilderInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class VaultDataBuilder implements BuilderInterface
{
    const PAYMENT_PROFILE_ID = 'profile_id';

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
     * @return array
     * @throws Exception
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        $extensionAttributes = $payment->getExtensionAttributes();

        if (!$extensionAttributes || !$extensionAttributes->getVaultPaymentToken()) {
            throw new Exception('The vault is not found.');
        }

        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        $result = [self::PAYMENT_PROFILE_ID => $paymentToken->getGatewayToken()];
        if ($payment->getAdditionalInformation(TransactionInterface::UNIQUE_ID)) {
            $result[TransactionInterface::UNIQUE_ID] = $payment->getAdditionalInformation(TransactionInterface::UNIQUE_ID);
        }

        return $result;
    }
}
