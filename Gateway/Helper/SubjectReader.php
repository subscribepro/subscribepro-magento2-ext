<?php

namespace Swarming\SubscribePro\Gateway\Helper;

use SubscribePro\Service\Transaction\TransactionInterface;

class SubjectReader extends \Magento\Payment\Gateway\Helper\SubjectReader
{
    /**
     * @param array $subject
     * @return \SubscribePro\Service\Transaction\TransactionInterface
     */
    public static function readTransaction(array $subject)
    {
        if (!isset($subject['transaction']) || !is_object($subject['transaction'])) {
            throw new \InvalidArgumentException('Response object does not exist');
        }

        if (!$subject['transaction'] instanceof TransactionInterface) {
            throw new \InvalidArgumentException('The object is not a class \SubscribePro\Service\Transaction\TransactionInterface.');
        }

        return $subject['transaction'];
    }

}
