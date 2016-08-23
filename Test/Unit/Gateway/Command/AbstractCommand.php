<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

class AbstractCommand extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Gateway\Request\BuilderInterface
     */
    protected $requestBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Gateway\Response\HandlerInterface
     */
    protected $handlerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Gateway\Validator\ValidatorInterface
     */
    protected $validatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $platformPaymentProfileServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Transaction
     */
    protected $platformTransactionServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    /**
     * @var array
     */
    protected $commandSubject = ['subject' => 'commandSubject'];

    protected function initProperties()
    {
        $this->requestBuilderMock = $this->getMockBuilder('Magento\Payment\Gateway\Request\BuilderInterface')
            ->disableOriginalConstructor()->getMock();
        $this->handlerMock = $this->getMockBuilder('Magento\Payment\Gateway\Response\HandlerInterface')
            ->disableOriginalConstructor()->getMock();
        $this->validatorMock = $this->getMockBuilder('Magento\Payment\Gateway\Validator\ValidatorInterface')
            ->disableOriginalConstructor()->getMock();
        $this->platformPaymentProfileServiceMock = $this->getMockBuilder('Swarming\SubscribePro\Platform\Service\PaymentProfile')
            ->disableOriginalConstructor()->getMock();
        $this->platformTransactionServiceMock = $this->getMockBuilder('Swarming\SubscribePro\Platform\Service\Transaction')
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @param array $requestData
     * @param \Exception $exception
     */
    protected function processTransactionFail(array $requestData, $exception)
    {
        $this->requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->commandSubject)
            ->willReturn($requestData);
        
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->handlerMock->expects($this->never())->method('handle');
    }

    /**
     * @param array $requestData
     * @param \PHPUnit_Framework_MockObject_MockObject $transaction
     */
    public function executeCommand(array $requestData, $transaction)
    {
        $this->requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->commandSubject)
            ->willReturn($requestData);
        
        $resultMock = $this->getMockBuilder('Magento\Payment\Gateway\Validator\ResultInterface')->getMock();
        $resultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with(['subject' => 'commandSubject', 'transaction' => $transaction])
            ->willReturn($resultMock);
        
        $this->handlerMock->expects($this->once())
            ->method('handle')
            ->with($this->commandSubject, ['transaction' => $transaction]);
    }

    /**
     * @return \SubscribePro\Service\Transaction\TransactionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createTransactionMock()
    {
        return $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
    }
}
