<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use SubscribePro\Service\Transaction\TransactionInterface;

class OrderDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        return [
            TransactionInterface::AMOUNT => (float) $this->formatPrice($this->subjectReader->readAmount($buildSubject)) * 100,
            TransactionInterface::CURRENCY_CODE => $order->getCurrencyCode(),
            TransactionInterface::ORDER_ID => $order->getOrderIncrementId(),
            TransactionInterface::IP => $order->getRemoteIp(),
            TransactionInterface::EMAIL => $order->getBillingAddress() ? $order->getBillingAddress()->getEmail() : null
        ];
    }
}
