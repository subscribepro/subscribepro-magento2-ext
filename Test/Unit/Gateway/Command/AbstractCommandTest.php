<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

class AbstractCommandTest extends AbstractCommand
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Command\AbstractCommand
     */
    protected $abstractCommand;

    /**
     * @var array
     */
    protected $requestData = ['requestData'];

    protected function setUp()
    {
        $this->initProperties();
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $arguments = $objectManagerHelper->getConstructArguments(
            'Swarming\SubscribePro\Gateway\Command\AbstractCommand',
            [
                'requestBuilder' => $this->requestBuilderMock,
                'handler' => $this->handlerMock,
                'validator' => $this->validatorMock,
                'platformPaymentProfileService' => $this->platformPaymentProfileServiceMock,
                'platformTransactionService' => $this->platformTransactionServiceMock,
                'logger' => $this->loggerMock
            ]
        );
        $this->abstractCommand = $this->getMockForAbstractClass(
            'Swarming\SubscribePro\Gateway\Command\AbstractCommand',
            $arguments,
            '',
            true, 
            true, 
            true,
            ['processTransaction']
        );

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
        $exception = new \Exception('message');
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
        
        $resultMock = $this->getMockBuilder('Magento\Payment\Gateway\Validator\ResultInterface')->getMock();
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
        
        $this->abstractCommand->expects($this->once())
            ->method('processTransaction')
            ->with($this->requestData)
            ->willReturn($transaction);
        
        $this->executeCommand($this->requestData, $transaction);
        $this->abstractCommand->execute($this->commandSubject);
    }
}
