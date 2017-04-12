<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Swarming\SubscribePro\Gateway\Command\RefundCommand;

class RefundCommandTest extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\RefundCommand
     */
    protected $refundCommand;

    protected function setUp()
    {
        $this->initProperties();
        $this->refundCommand = new RefundCommand(
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
            ->method('credit')
            ->with($refTransactionId, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->refundCommand->execute($this->commandSubject);
    }
}
