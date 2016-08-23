<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Helper\Formatter;
use SubscribePro\Service\Transaction\TransactionInterface;

class OrderDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        return [
            TransactionInterface::AMOUNT => $this->formatPrice(SubjectReader::readAmount($buildSubject))*100,
            TransactionInterface::CURRENCY_CODE => $order->getCurrencyCode(),
            TransactionInterface::ORDER_ID => $order->getOrderIncrementId(),
            TransactionInterface::IP => $order->getRemoteIp(),
            TransactionInterface::EMAIL => $order->getBillingAddress() ? $order->getBillingAddress()->getEmail() : null
        ];
    }
}
