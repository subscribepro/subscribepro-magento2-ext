<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Command\ApplePay;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Model\Order\Payment;

class CaptureStrategyCommand implements CommandInterface
{
    public const PURCHASE = 'purchase';
    public const SETTLEMENT = 'settlement';

    /**
     * @var \Magento\Payment\Gateway\Command\CommandPoolInterface
     */
    protected $commandPool;

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @param \Magento\Payment\Gateway\Command\CommandPoolInterface $commandPool
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Magento\Payment\Gateway\Command\CommandPoolInterface $commandPool,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->commandPool = $commandPool;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject): void
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($commandSubject);

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
    protected function getCommand(Payment $payment): string
    {
        return $payment->getParentTransactionId() ? self::SETTLEMENT : self::PURCHASE;
    }
}
