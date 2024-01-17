<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class WalletPaymentBuilder implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        return [
            PaymentProfileInterface::CREDITCARD_MONTH => $this->getCreditCardMonth($buildSubject),
            PaymentProfileInterface::CREDITCARD_YEAR => $this->getCreditCardYear($buildSubject),
            PaymentProfileInterface::CREDITCARD_TYPE => $buildSubject['creditcard_type'] ?? null,
            PaymentProfileInterface::CREDITCARD_LAST_DIGITS => $buildSubject['creditcard_last_digits'] ?? null,
            PaymentProfileInterface::CREDITCARD_FIRST_DIGITS => $buildSubject['creditcard_first_digits'] ?? null,
            PaymentProfileInterface::BILLING_ADDRESS => $this->getBillingAddress($buildSubject),

            PaymentDataBuilder::PAYMENT_METHOD_TOKEN => $this->getPaymentToken($buildSubject),
            VaultConfigProvider::IS_ACTIVE_CODE => 1,
        ];
    }

    /**
     * @param array $buildSubject
     * @return string
     */
    private function getCreditCardMonth(array $buildSubject)
    {
        if (empty($buildSubject['creditcard_month'])) {
            throw new \InvalidArgumentException('Credit card month expiration is not passed.');
        }
        return $buildSubject['creditcard_month'];
    }

    /**
     * @param array $buildSubject
     * @return string
     */
    private function getCreditCardYear(array $buildSubject)
    {
        if (empty($buildSubject['creditcard_year'])) {
            throw new \InvalidArgumentException('Credit card year expiration is not passed.');
        }
        return $buildSubject['creditcard_year'];
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    private function getBillingAddress(array $buildSubject)
    {
        if (empty($buildSubject['billing_address'])) {
            throw new \InvalidArgumentException('Billing address is not passed.');
        }
        return $buildSubject['billing_address'];
    }

    /**
     * @param array $buildSubject
     * @return string
     */
    private function getPaymentToken(array $buildSubject)
    {
        if (empty($buildSubject['token'])) {
            throw new \InvalidArgumentException('Payment token is not passed.');
        }
        return $buildSubject['token'];
    }
}
