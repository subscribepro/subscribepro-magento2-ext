<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class VaultDataBuilder implements BuilderInterface
{
    const PAYMENT_PROFILE_ID = 'profile_id';
    const ORDER_TOKEN = 'subscribe_pro_order_token';

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
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        $extensionAttributes = $payment->getExtensionAttributes();

        if (!$extensionAttributes || !$extensionAttributes->getVaultPaymentToken()) {
            throw new \UnexpectedValueException(__('The vault is not found.'));
        }

        $paymentToken = $extensionAttributes->getVaultPaymentToken();

        $result = [self::PAYMENT_PROFILE_ID => $paymentToken->getGatewayToken()];

        $payment->setAdditionalInformation('public_hash', 'yay');

        $uniqueId = $payment->getAdditionalInformation(TransactionInterface::UNIQUE_ID);
        if ($payment->getAdditionalInformation(TransactionInterface::UNIQUE_ID)) {
            $result[TransactionInterface::UNIQUE_ID] = $uniqueId;
        }

        $subscribeProOrderToken = $payment->getAdditionalInformation(TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN);
        if ($payment->getAdditionalInformation(TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN)) {
            $result[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN] = $subscribeProOrderToken;
        }

        return $result;
    }
}
