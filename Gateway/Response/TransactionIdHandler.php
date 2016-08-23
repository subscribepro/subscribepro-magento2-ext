<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use SubscribePro\Service\Transaction\TransactionInterface;

class TransactionIdHandler implements HandlerInterface
{
    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
        $orderPayment = $paymentDO->getPayment();
        if ($orderPayment instanceof Payment) {
            $transaction = SubjectReader::readTransaction($response);

            $orderPayment->setTransactionId($transaction->getId());
            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction());
            $orderPayment->setShouldCloseParentTransaction($this->shouldCloseParentTransaction($orderPayment));

            $orderPayment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $this->getTransactionDetails($transaction));
        }
    }

    /**
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @return array
     */
    protected function getTransactionDetails($transaction)
    {
        $details = $transaction->toArray();
        unset($details[TransactionInterface::GATEWAY_SPECIFIC_RESPONSE]);
        return $details;
    }

    /**
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment)
    {
        return false;
    }
}
