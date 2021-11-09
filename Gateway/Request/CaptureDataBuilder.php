<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use SubscribePro\Service\Transaction\TransactionInterface;

class CaptureDataBuilder implements BuilderInterface
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $transactionId = $payment->getParentTransactionId();
        if (!$transactionId) {
            throw new LocalizedException(__('Parent transaction is not found.'));
        }

        $amount = $currency = null;
        try {
            $amount = $this->formatPrice($this->subjectReader->readAmount($buildSubject))*100;
            $currency = $order->getCurrencyCode();
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (\InvalidArgumentException $e) {
            // pass
        }

        return [
            TransactionInterface::REF_TRANSACTION_ID => $transactionId,
            TransactionInterface::AMOUNT => $amount,
            TransactionInterface::CURRENCY_CODE => $currency
        ];
    }
}
