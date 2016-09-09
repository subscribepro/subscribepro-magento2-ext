<?php

namespace Swarming\SubscribePro\Gateway\Validator;

use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use SubscribePro\Service\Transaction\TransactionInterface;

class ResponseValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;
    
    /**
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }
    
    /**
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     * @throws \InvalidArgumentException
     */
    public function validate(array $validationSubject)
    {
        $transaction = $this->subjectReader->readTransaction($validationSubject);
        return $this->createResult($transaction->getState() == TransactionInterface::STATE_SUCCEEDED);
    }
}
