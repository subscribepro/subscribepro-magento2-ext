<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

class RefundCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     */
    protected function processTransaction(array $requestData)
    {
        $transaction = $this->sdkTransactionService->createTransaction($requestData);
        $this->sdkTransactionService->credit($transaction->getRefTransactionId(), $transaction);

        return $transaction;
    }
}
