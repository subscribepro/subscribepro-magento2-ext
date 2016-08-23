<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

class PurchaseCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     */
    protected function processTransaction(array $requestData)
    {
        $profile = $this->sdkPaymentProfileService->createProfile($requestData);
        $this->sdkPaymentProfileService->saveProfile($profile);

        $transaction = $this->sdkTransactionService->createTransaction($requestData);
        $this->sdkTransactionService->purchaseByProfile($profile->getId(), $transaction);

        return $transaction;
    }
}
