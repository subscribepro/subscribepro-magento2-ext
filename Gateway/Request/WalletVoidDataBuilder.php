<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class WalletVoidDataBuilder implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        return [
            TransactionInterface::REF_TRANSACTION_ID => $this->getRefTransactionId($buildSubject),
        ];
    }

    /**
     * @param array $buildSubject
     * @return string
     */
    private function getRefTransactionId(array $buildSubject)
    {
        if (empty($buildSubject[TransactionInterface::REF_TRANSACTION_ID])) {
            throw new \InvalidArgumentException('ID is not passed.');
        }
        return $buildSubject[TransactionInterface::REF_TRANSACTION_ID];
    }
}
