<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Swarming\SubscribePro\Gateway\Command\AbstractCommand;
use Swarming\SubscribePro\Test\Unit\Gateway\Command\AbstractCommand as TestAbstractCommand;

class AbstractCommandTest extends TestAbstractCommand
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Command\AbstractCommand
     */
    protected $abstractCommand;

    /**
     * @var array
     */
    protected $requestData = ['requestData'];

    protected function setUp(): void
    {
        $this->initProperties();
        $this->abstractCommand = $this->getMockBuilder(AbstractCommand::class)
            ->setConstructorArgs([
                $this->requestBuilderMock,
                $this->platformMock,
                $this->storeManagerMock,
                $this->subjectReaderMock,
                $this->handlerMock,
                $this->validatorMock,
                $this->platformPaymentProfileServiceMock,
                $this->platformTransactionServiceMock,
                $this->loggerMock,
            ])
            ->setMethods(['processTransaction'])
            ->getMockForAbstractClass();

        $this->requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->commandSubject)
            ->willReturn($this->requestData);
    }

    /**
     * @expectedException \Magento\Payment\Gateway\Command\CommandException
     * @expectedExceptionMessage Transaction has been declined. Please try again later.
     */
    public function testExecuteIfFailToProcessTransaction()
    {
        $exception = new \InvalidArgumentException('message');

        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);

        $this->abstractCommand->expects($this->once())
            ->method('processTransaction')
            ->with($this->requestData)
            ->willThrowException($exception);

        $this->processTransactionFail($this->requestData, $exception);
        $this->abstractCommand->execute($this->commandSubject);
    }

    /**
     * @expectedException \Magento\Payment\Gateway\Command\CommandException
     * @expectedExceptionMessage Transaction has been declined. Please try again later.
     */
    public function testFailToExecuteIfNotValid()
    {
        $transaction = $this->createTransactionMock();
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);

        $resultMock = $this->getMockBuilder(ResultInterface::class)->getMock();
        $resultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->abstractCommand->expects($this->once())
            ->method('processTransaction')
            ->with($this->requestData)
            ->willReturn($transaction);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with(['subject' => 'commandSubject', 'transaction' => $transaction])
            ->willReturn($resultMock);

        $this->handlerMock->expects($this->never())->method('handle');

        $this->abstractCommand->execute($this->commandSubject);
    }

    public function testExecute()
    {
        $transaction = $this->createTransactionMock();
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->abstractCommand->expects($this->once())
            ->method('processTransaction')
            ->with($this->requestData)
            ->willReturn($transaction);

        $this->executeCommand($this->requestData, $transaction);
        $this->abstractCommand->execute($this->commandSubject);
    }
}
