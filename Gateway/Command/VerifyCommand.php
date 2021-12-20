<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Command;

use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class VerifyCommand extends AbstractProfileCreatorCommand
{
    /**
     * @param array $requestData
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \InvalidArgumentException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function processTransaction(array $requestData)
    {
        if (empty($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN])) {
            throw new \InvalidArgumentException('Payment token is not passed');
        }

        $transaction = $this->platformTransactionService->createTransaction($requestData);
        $profile = $this->createProfile($requestData);
        $this->platformTransactionService->verifyProfile($profile->getId(), $transaction);

        return $transaction;
    }
}
