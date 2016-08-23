<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Sales\Model\Order\Payment;

class VoidHandler extends TransactionIdHandler
{
    /**
     * @param Payment $orderPayment
     * @param \Braintree\Transaction $transaction
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function setTransactionId(Payment $orderPayment, \Braintree\Transaction $transaction)
    {
        return;
    }

    /**
     * @return bool
     */
    protected function shouldCloseTransaction()
    {
        return true;
    }

    /**
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment)
    {
        return true;
    }
}
