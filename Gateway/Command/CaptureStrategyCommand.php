<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

class CaptureStrategyCommand implements CommandInterface
{
    const PURCHASE = 'purchase';
    const SETTLEMENT = 'settlement';

    /**
     * @var \Magento\Payment\Gateway\Command\CommandPoolInterface
     */
    protected $commandPool;

    /**
     * @param \Magento\Payment\Gateway\Command\CommandPoolInterface $commandPool
     */
    public function __construct(
        \Magento\Payment\Gateway\Command\CommandPoolInterface $commandPool
    ) {
        $this->commandPool = $commandPool;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var \Magento\Sales\Model\Order\Payment $paymentInfo */
        $paymentInfo = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($paymentInfo);

        $command = $this->getCommand($paymentInfo);
        $this->commandPool->get($command)->execute($commandSubject);
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return string
     */
    protected function getCommand(Payment $payment)
    {
        if ($payment->getParentTransactionId()) {
            return self::SETTLEMENT;
        }

        return self::PURCHASE;
    }
}
