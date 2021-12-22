<?php

namespace Swarming\SubscribePro\Gateway\Helper;

use SubscribePro\Service\Transaction\TransactionInterface;
use Magento\Payment\Gateway\Helper\SubjectReader as SubjectReaderHelper;

class SubjectReader
{
    /**
     * @param array $subject
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface
     * @throws \InvalidArgumentException
     * @codeCoverageIgnore
     */
    public function readPayment(array $subject)
    {
        return SubjectReaderHelper::readPayment($subject);
    }

    /**
     * @param array $subject
     * @return mixed
     * @throws \InvalidArgumentException
     * @codeCoverageIgnore
     */
    public function readAmount(array $subject)
    {
        return SubjectReaderHelper::readAmount($subject);
    }

    /**
     * @param array $subject
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     * @throws \InvalidArgumentException
     */
    public function readTransaction(array $subject)
    {
        if (!isset($subject['transaction']) || !is_object($subject['transaction'])) {
            throw new \InvalidArgumentException('Response object does not exist');
        }

        if (!$subject['transaction'] instanceof TransactionInterface) {
            throw new \InvalidArgumentException(
                'The object is not a class \SubscribePro\Service\Transaction\TransactionInterface.'
            );
        }

        return $subject['transaction'];
    }

    /**
     * @param array $subject
     * @return \Magento\Framework\DataObject
     * @throws \InvalidArgumentException
     */
    public function readTransferObject(array $subject)
    {
        if (!$subject['transfer'] instanceof \Magento\Framework\DataObject) {
            throw new \InvalidArgumentException('Transfer data object should be provided');
        }

        return $subject['transfer'];
    }
}
