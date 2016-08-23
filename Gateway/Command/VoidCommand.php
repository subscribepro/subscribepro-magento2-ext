<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class VoidCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     */
    protected function processTransaction(array $requestData)
    {
        $transaction = $this->sdkTransactionService->void($requestData[TransactionInterface::REF_TRANSACTION_ID]);

        return $transaction;
    }
}
