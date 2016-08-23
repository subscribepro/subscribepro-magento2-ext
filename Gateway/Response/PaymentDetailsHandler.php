<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use SubscribePro\Service\Transaction\TransactionInterface;

class PaymentDetailsHandler implements HandlerInterface
{
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
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \InvalidArgumentException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);

        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        $payment->setCcTransId($transaction->getId());
        $payment->setLastTransId($transaction->getId());

        $payment->setCcAvsStatus($transaction->getAvsCode());
        $payment->setCcCidStatus($transaction->getCvvCode());

        $payment->setAdditionalInformation('transaction_id', $transaction->getId());
        $payment->setAdditionalInformation('transaction_type', $transaction->getType());
        $payment->setAdditionalInformation(TransactionInterface::GATEWAY_TYPE, $transaction->getGatewayType());
        $payment->setAdditionalInformation(TransactionInterface::AVS_CODE, $transaction->getAvsCode());
        $payment->setAdditionalInformation(TransactionInterface::AVS_MESSAGE, $transaction->getAvsMessage());
        $payment->setAdditionalInformation(TransactionInterface::CVV_CODE, $transaction->getCvvCode());
        $payment->setAdditionalInformation(TransactionInterface::CVV_MESSAGE, $transaction->getCvvMessage());
        $payment->setAdditionalInformation(TransactionInterface::RESPONSE_MESSAGE, $transaction->getResponseMessage());
    }
}
