<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use SubscribePro\Service\Transaction\TransactionInterface;

class VoidCommandTest extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\VoidCommand
     */
    protected $voidCommand;

    protected function setUp()
    {
        $this->initProperties();
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->voidCommand = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Command\VoidCommand',
            [
                'requestBuilder' => $this->requestBuilderMock,
                'handler' => $this->handlerMock,
                'validator' => $this->validatorMock,
                'platformPaymentProfileService' => $this->platformPaymentProfileServiceMock,
                'platformTransactionService' => $this->platformTransactionServiceMock,
                'logger' => $this->loggerMock
            ]
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
        $exception = new \Exception('Referenced transaction id is not passed');
        
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
        
        $this->platformTransactionServiceMock->expects($this->once())
            ->method('void')
            ->with($refTransactionId)
            ->willReturn($transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->voidCommand->execute($this->commandSubject);
    }
}
