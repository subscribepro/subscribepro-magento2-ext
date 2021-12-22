<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class VoidCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     * @throws \InvalidArgumentException
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[TransactionInterface::REF_TRANSACTION_ID])) {
            throw new \InvalidArgumentException(__('Referenced transaction id is not passed'));
        }

        $transaction = $this->platformTransactionService->void($requestData[TransactionInterface::REF_TRANSACTION_ID]);

        return $transaction;
    }
}
