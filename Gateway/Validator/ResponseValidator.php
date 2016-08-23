<?php

namespace Swarming\SubscribePro\Gateway\Validator;

use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use SubscribePro\Service\Transaction\TransactionInterface;

class ResponseValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $transaction = SubjectReader::readTransaction($validationSubject);
        return $this->createResult($transaction->getState() == TransactionInterface::STATE_SUCCEEDED);
    }
}
