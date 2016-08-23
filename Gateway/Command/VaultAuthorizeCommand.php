<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultAuthorizeCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws Exception
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[VaultDataBuilder::PAYMENT_PROFILE_ID])) {
            throw new Exception('Payment profile is not passed');
        }

        $transaction = $this->platformTransactionService->createTransaction($requestData);
        $this->platformTransactionService->authorizeByProfile($requestData[VaultDataBuilder::PAYMENT_PROFILE_ID], $transaction);

        return $transaction;
    }
}
