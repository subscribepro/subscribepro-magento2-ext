<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class PurchaseCommand extends AbstractProfileCreatorCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN])) {
            throw new Exception('Payment token is not passed');
        }

        $transaction = $this->platformTransactionService->createTransaction($requestData);
        if (!empty($requestData[VaultConfigProvider::IS_ACTIVE_CODE]) && $requestData[VaultConfigProvider::IS_ACTIVE_CODE]) {
            $profile = $this->createProfile($requestData);
            $this->platformTransactionService->purchaseByProfile([
                VaultDataBuilder::ORDER_TOKEN => $profile->getId()
            ], $transaction);
        } else {
            $this->platformTransactionService->purchaseByToken($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN], $transaction);
        }

        return $transaction;
    }
}
