<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Helper\Formatter;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use SubscribePro\Service\Transaction\TransactionInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfile;

class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;

    const PAYMENT_METHOD_TOKEN = 'payment_method_token';

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        return [
            TransactionInterface::AMOUNT => $this->formatPrice(SubjectReader::readAmount($buildSubject))*100,
            TransactionInterface::CURRENCY_CODE => $order->getCurrencyCode(),
            TransactionInterface::ORDER_ID => $order->getOrderIncrementId(),
            TransactionInterface::IP => $order->getRemoteIp(),
            TransactionInterface::EMAIL => $order->getBillingAddress() ? $order->getBillingAddress()->getEmail() : null,
            PaymentProfile::MAGENTO_CUSTOMER_ID => $order->getCustomerId(),
            self::PAYMENT_METHOD_TOKEN => $payment->getAdditionalInformation(self::PAYMENT_METHOD_TOKEN),
            VaultConfigProvider::IS_ACTIVE_CODE => $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)
        ];
    }
}
