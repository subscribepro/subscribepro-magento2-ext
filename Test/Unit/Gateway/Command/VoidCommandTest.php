<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Command\VoidCommand;

class VoidCommandTest extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\VoidCommand
     */
    protected $voidCommand;

    protected function setUp(): void
    {
        $this->initProperties();
        $this->voidCommand = new VoidCommand(
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

    /**
     * @expectedException \Magento\Payment\Gateway\Command\CommandException
     * @expectedExceptionMessage Transaction has been declined. Please try again later.
     * @dataProvider executeIfFailToProcessTransactionDataProvider
     * @param array $requestData
     */
    public function testExecuteIfFailToProcessTransaction(array $requestData)
    {
        $exception = new \InvalidArgumentException('Referenced transaction id is not passed');
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->processTransactionFail($requestData, $exception);
        $this->voidCommand->execute($this->commandSubject);
    }

    /**
     * @return array
     */
    public function executeIfFailToProcessTransactionDataProvider()
    {
        return [
            'Referenced transaction id is not set' => [
                'requestData' => []
            ],
            'Referenced transaction id is empty' => [
                'requestData' => [TransactionInterface::REF_TRANSACTION_ID => '']
            ],
        ];
    }

    public function testExecute()
    {
        $refTransactionId = 542;
        $requestData = [TransactionInterface::REF_TRANSACTION_ID => $refTransactionId];
        $transactionMock = $this->createTransactionMock();
        $this->executeSetPlatformWebsite($this->subjectReaderMock, $this->storeManagerMock, $this->platformMock);
        $this->platformTransactionServiceMock->expects($this->once())
            ->method('void')
            ->with($refTransactionId)
            ->willReturn($transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->voidCommand->execute($this->commandSubject);
    }
}
