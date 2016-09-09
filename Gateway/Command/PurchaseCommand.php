<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class PurchaseCommand extends AbstractProfileCreatorCommand implements CommandInterface
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
        if (empty($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN])) {
            throw new Exception('Payment token is not passed');
        }

        if (!empty($requestData[VaultConfigProvider::IS_ACTIVE_CODE]) && $requestData[VaultConfigProvider::IS_ACTIVE_CODE]) {
            $profile = $this->createProfile($requestData);
            $transaction = $this->platformTransactionService->createTransaction($requestData);
            $this->platformTransactionService->purchaseByProfile($profile->getId(), $transaction);
        } else {
            $transaction = $this->platformTransactionService->createTransaction($requestData);
            $this->platformTransactionService->purchaseByToken($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN], $transaction);
        }

        return $transaction;
    }
}
