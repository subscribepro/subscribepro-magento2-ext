<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Command;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Platform\Service\PaymentProfile as PaymentProfileService;
use Swarming\SubscribePro\Platform\Service\Transaction as TransactionService;

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
        $this->requestBuilderMock = $this->getMockBuilder(BuilderInterface::class)->getMock();
        $this->handlerMock = $this->getMockBuilder(HandlerInterface::class)->getMock();
        $this->validatorMock = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $this->platformPaymentProfileServiceMock = $this->getMockBuilder(PaymentProfileService::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformTransactionServiceMock = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
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
        
        $resultMock = $this->getMockBuilder(ResultInterface::class)->getMock();
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
        return $this->getMockBuilder(TransactionInterface::class)->getMock();
    }
}
