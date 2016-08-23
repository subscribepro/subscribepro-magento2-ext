<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Swarming\SubscribePro\Gateway\Command\CaptureStrategyCommand;

class CaptureStrategyCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\CaptureStrategyCommand
     */
    protected $captureStrategyCommand;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Gateway\Command\CommandPoolInterface
     */
    protected $commandPoolMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp()
    {
        $this->commandPoolMock = $this->getMockBuilder('Magento\Payment\Gateway\Command\CommandPoolInterface')
            ->disableOriginalConstructor()->getMock();
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->captureStrategyCommand = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Command\CaptureStrategyCommand',
            [
                'commandPool' => $this->commandPoolMock,
                'subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testFailToExecuteIfPaymentInfoNotValid()
    {
        $commandSubject = ['subject'];
        $paymentInfoMock = $this->getMockBuilder('Magento\Payment\Model\InfoInterface')->getMock();
        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($commandSubject)
            ->willReturn($paymentDOMock);

        $this->captureStrategyCommand->execute($commandSubject);
    }
    
    /**
     * @dataProvider executeDataProvider
     * @param array $commandSubject
     * @param int|null $parentTransactionId
     * @param string $command
     */
    public function testExecute($commandSubject, $parentTransactionId, $command)
    {
        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getParentTransactionId')
            ->willReturn($parentTransactionId);
        
        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);
        
        $commandMock = $this->getMockBuilder('Magento\Payment\Gateway\CommandInterface')->getMock();
        $commandMock->expects($this->once())->method('execute')->willReturn($commandSubject);
        
        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($commandSubject)
            ->willReturn($paymentDOMock);

        $this->commandPoolMock->expects($this->once())
            ->method('get')
            ->with($command)
            ->willReturn($commandMock);

        $this->captureStrategyCommand->execute($commandSubject);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'with parent transaction id' => [
                'commandSubject' => ['subject'],
                'parentTransactionId' => 123,
                'command' => CaptureStrategyCommand::SETTLEMENT
            ],
            'without parent transaction id' => [
                'commandSubject' => ['subject'],
                'parentTransactionId' => null,
                'command' => CaptureStrategyCommand::PURCHASE
            ]
        ];
    }
}
