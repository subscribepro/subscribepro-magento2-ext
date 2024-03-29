<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
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
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);

        /** @var Payment $payment */
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

        $payment->setAdditionalInformation(TransactionInterface::STATE, $transaction->getState());
        $payment->setAdditionalInformation(TransactionInterface::TOKEN, $transaction->getToken());

        $gatewaySpecificResponse = $transaction->getGatewaySpecificResponse();
        if (!empty($gatewaySpecificResponse)) {
            $payment->setAdditionalInformation(
                TransactionInterface::GATEWAY_SPECIFIC_RESPONSE,
                $gatewaySpecificResponse
            );
        }

        if ($transaction->getState() === TransactionInterface::STATE_PENDING) {
            $payment->setIsTransactionPending(true);
        }
    }
}
