<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultAuthorizeCommand extends AbstractCommand implements CommandInterface
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \InvalidArgumentException
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[VaultDataBuilder::PAYMENT_PROFILE_ID])) {
            throw new \InvalidArgumentException(__('Payment profile was not passed'));
        }

        $authorizeData = [];
        $authorizeData[VaultDataBuilder::PAYMENT_PROFILE_ID] = $requestData[VaultDataBuilder::PAYMENT_PROFILE_ID];
        if (isset($requestData[VaultDataBuilder::ORDER_TOKEN])) {
            $authorizeData[VaultDataBuilder::ORDER_TOKEN] = $requestData[VaultDataBuilder::ORDER_TOKEN];
        }

        $transaction = $this->platformTransactionService->createTransaction($requestData);
        $this->platformTransactionService->authorizeByProfile($authorizeData, $transaction);

        return $transaction;
    }
}
