<?php

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\Address\AddressInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

/**
 * @method \SubscribePro\Service\Transaction\TransactionService getService($websiteId = null)
 */
class Transaction extends AbstractService
{
    /**
     * @param array $transactionData
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     */
    public function createTransaction(array $transactionData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createTransaction($transactionData);
    }

    /**
     * @param int $transactionId
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadTransaction($transactionId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadTransaction($transactionId);
    }

    /**
     * @param int $paymentProfileId
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function verifyProfile($paymentProfileId, TransactionInterface $transaction, $websiteId = null)
    {
        return $this->getService($websiteId)->verifyProfile($paymentProfileId, $transaction);
    }

    /**
     * @param array $paymentProfileData
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function authorizeByProfile($paymentProfileData, TransactionInterface $transaction, $websiteId = null)
    {
        return $this->getService($websiteId)->authorizeByProfile($paymentProfileData, $transaction);
    }

    /**
     * @param int $paymentProfileId
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function purchaseByProfile($paymentProfileId, TransactionInterface $transaction, $websiteId = null)
    {
        return $this->getService($websiteId)->purchaseByProfile($paymentProfileId, $transaction);
    }

    /**
     * @param string $token
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @param \SubscribePro\Service\Address\AddressInterface|null $platformAddress
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Token\TokenInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function authorizeByToken($token, TransactionInterface $transaction, AddressInterface $platformAddress = null, $websiteId = null)
    {
        return $this->getService($websiteId)->authorizeByToken($token, $transaction, $platformAddress);
    }

    /**
     * @param string $token
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @param \SubscribePro\Service\Address\AddressInterface|null $platformAddress
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Token\TokenInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function purchaseByToken($token, TransactionInterface $transaction, AddressInterface $platformAddress = null, $websiteId = null)
    {
        return $this->getService($websiteId)->purchaseByToken($token, $transaction, $platformAddress);
    }

    /**
     * @param int $transactionId
     * @param \SubscribePro\Service\Transaction\TransactionInterface|null $transaction
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function capture($transactionId, TransactionInterface $transaction = null, $websiteId = null)
    {
        return $this->getService($websiteId)->capture($transactionId, $transaction);
    }

    /**
     * @param int $transactionId
     * @param \SubscribePro\Service\Transaction\TransactionInterface|null $transaction
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function credit($transactionId, TransactionInterface $transaction = null, $websiteId = null)
    {
        return $this->getService($websiteId)->credit($transactionId, $transaction);
    }

    /**
     * @param int $transactionId
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function void($transactionId, $websiteId = null)
    {
        return $this->getService($websiteId)->void($transactionId);
    }
}
