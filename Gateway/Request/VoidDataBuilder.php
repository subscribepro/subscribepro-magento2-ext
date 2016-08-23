<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Helper\Formatter;
use SubscribePro\Service\Transaction\TransactionInterface;

class VoidDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        
        return [
            TransactionInterface::REF_TRANSACTION_ID => $payment->getParentTransactionId(),
        ];
    }
}
