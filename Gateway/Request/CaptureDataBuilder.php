<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Helper\Formatter;
use SubscribePro\Service\Transaction\TransactionInterface;

class CaptureDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @param array $buildSubject
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $transactionId = $payment->getParentTransactionId();
        if (!$transactionId) {
            throw new LocalizedException(__('Parent transaction is not found.'));
        }

        $amount = $currency = null;
        try {
            $amount = $this->formatPrice(SubjectReader::readAmount($buildSubject))*100;
            $currency = $order->getCurrencyCode();
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
