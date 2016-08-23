<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

class CaptureCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function processTransaction(array $requestData)
    {
        $transaction = $this->platformTransactionService->createTransaction($requestData);
        $this->platformTransactionService->capture($transaction->getRefTransactionId(), $transaction);

        return $transaction;
    }
}
