<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Swarming\SubscribePro\Gateway\Command\CaptureCommand;

class CaptureCommandTest extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\CaptureCommand
     */
    protected $captureCommand;

    protected function setUp()
    {
        $this->initProperties();
        $this->captureCommand = new CaptureCommand(
            $this->requestBuilderMock,
            $this->platformMock,
            $this->storeManagerMock,
            $this->subjectReaderMock,
            $this->handlerMock,
            $this->validatorMock,
            $this->platformPaymentProfileServiceMock,
            $this->platformTransactionServiceMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $requestData = ['data'];
        $transactionMock = $this->createTransactionMock();
        $refTransactionId = 432;
        $transactionMock->expects($this->once())->method('getRefTransactionId')->willReturn($refTransactionId);
        
        $this->platformTransactionServiceMock->expects($this->once())
            ->method('createTransaction')
            ->with($requestData)
            ->willReturn($transactionMock);

        $this->platformTransactionServiceMock->expects($this->once())
            ->method('capture')
            ->with($refTransactionId, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->captureCommand->execute($this->commandSubject);
    }
}
