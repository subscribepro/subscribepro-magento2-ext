<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class AuthorizeCommand extends AbstractProfileCreatorCommand implements CommandInterface
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
        if (empty($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN])) {
            throw new \InvalidArgumentException(__('Payment token is not passed'));
        }

        $transaction = $this->platformTransactionService->createTransaction($requestData);
        if (!empty($requestData[VaultConfigProvider::IS_ACTIVE_CODE])
            && $requestData[VaultConfigProvider::IS_ACTIVE_CODE]) {
            $profile = $this->createProfile($requestData);
            $this->platformTransactionService->authorizeByProfile([
                VaultDataBuilder::PAYMENT_PROFILE_ID => $profile->getId()
            ], $transaction);
        } else {
            $this->platformTransactionService->authorizeByToken(
                $requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN],
                $transaction
            );
        }

        return $transaction;
    }
}
