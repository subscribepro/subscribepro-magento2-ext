<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Helper\Formatter;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;

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
            PaymentProfileInterface::CREDITCARD_NUMBER => $payment->getAdditionalInformation('cc_number'),
            PaymentProfileInterface::CREDITCARD_MONTH => $payment->getAdditionalInformation('cc_exp_month'),
            PaymentProfileInterface::CREDITCARD_YEAR => $payment->getAdditionalInformation('cc_exp_year'),
            PaymentProfileInterface::CREDITCARD_VERIFICATION_VALUE => $payment->getAdditionalInformation('cc_cid'),
        ];
    }
}
