<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class WalletCustomerDataBuilder implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        return [
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => $this->getCustomerId($buildSubject),
            TransactionInterface::EMAIL => $this->getCustomerEmail($buildSubject),
        ];
    }

    /**
     * @param array $buildSubject
     * @return int
     */
    private function getCustomerId(array $buildSubject)
    {
        if (empty($buildSubject['customer_id'])) {
            throw new \InvalidArgumentException('Customer Id is not passed.');
        }
        return $buildSubject['customer_id'];
    }

    /**
     * @param array $buildSubject
     * @return string
     */
    private function getCustomerEmail(array $buildSubject)
    {
        if (empty($buildSubject['customer_email'])) {
            throw new \InvalidArgumentException('Customer email is not passed.');
        }
        return $buildSubject['customer_email'];
    }
}
