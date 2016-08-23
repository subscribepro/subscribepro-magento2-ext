<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

class CaptureCommandTest extends AbstractProfileCreatorCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\CaptureCommand
     */
    protected $captureCommand;

    protected function setUp()
    {
        $this->initProperties();
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->captureCommand = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Command\CaptureCommand',
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
