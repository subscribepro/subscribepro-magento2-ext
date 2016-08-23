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
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[VaultDataBuilder::PAYMENT_PROFILE_ID])) {
            throw new Exception('Payment profile is not passed');
        }

        $transaction = $this->sdkTransactionService->createTransaction($requestData);
        $this->sdkTransactionService->purchaseByProfile($requestData[VaultDataBuilder::PAYMENT_PROFILE_ID], $transaction);

        return $transaction;
    }
}
