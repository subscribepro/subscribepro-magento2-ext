<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultPurchaseCommand extends AbstractCommand implements CommandInterface
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
            throw new Exception('Payment profile was not passed');
        }

        $purchaseData = [];
        $purchaseData[VaultDataBuilder::PAYMENT_PROFILE_ID] = $requestData[VaultDataBuilder::PAYMENT_PROFILE_ID];
        if (isset($requestData[VaultDataBuilder::ORDER_TOKEN])) {
            $purchaseData[VaultDataBuilder::ORDER_TOKEN] = $requestData[VaultDataBuilder::ORDER_TOKEN];
        }

        $transaction = $this->platformTransactionService->createTransaction($requestData);
        $this->platformTransactionService->purchaseByProfile($purchaseData, $transaction);

        return $transaction;
    }
}
