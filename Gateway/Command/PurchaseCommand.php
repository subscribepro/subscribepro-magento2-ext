<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class PurchaseCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws Exception
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN])) {
            throw new Exception('Payment token is not passed');
        }

        if (!empty($requestData[VaultConfigProvider::IS_ACTIVE_CODE]) && $requestData[VaultConfigProvider::IS_ACTIVE_CODE]) {
            $profile = $this->sdkPaymentProfileService->createProfile($requestData);
            $this->sdkPaymentProfileService->saveToken($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN], $profile);

            $transaction = $this->sdkTransactionService->createTransaction($requestData);
            $this->sdkTransactionService->purchaseByProfile($profile->getId(), $transaction);
        } else {
            $transaction = $this->sdkTransactionService->createTransaction($requestData);
            $this->sdkTransactionService->purchaseByToken($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN], $transaction);
        }

        return $transaction;
    }
}
