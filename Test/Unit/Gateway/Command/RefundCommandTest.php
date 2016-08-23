<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

class RefundCommandTest extends AbstractProfileCreatorCommand
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Command\RefundCommand
     */
    protected $refundCommand;

    protected function setUp()
    {
        $this->initProperties();
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->refundCommand = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Command\RefundCommand',
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
            ->method('credit')
            ->with($refTransactionId, $transactionMock);

        $this->executeCommand($requestData, $transactionMock);
        $this->refundCommand->execute($this->commandSubject);
    }
}
