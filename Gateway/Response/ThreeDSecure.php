<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class ThreeDSecure implements HandlerInterface
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);

        $payment = $paymentDO->getPayment();
        $payment->setAdditionalInformation(TransactionInterface::STATE, $transaction->getState());
        $payment->setAdditionalInformation(TransactionInterface::TOKEN, $transaction->getToken());

        if ($transaction->getState() === TransactionInterface::STATE_PENDING) {
            $payment->setIsTransactionPending(true);
        }
    }
}
