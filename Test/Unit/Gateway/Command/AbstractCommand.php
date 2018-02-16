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
use Swarming\SubscribePro\Platform\Platform;
use Magento\Store\Model\StoreManagerInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Store\Api\Data\StoreInterface;

class AbstractCommand extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Gateway\Request\BuilderInterface
     */
    protected $requestBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Platform
     */
    protected $platformMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

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
        $this->platformMock = $this->getMockBuilder(Platform::class)
            ->disableOriginalConstructor()->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)->getMock();
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

    protected function executeSetPlatformWebsite($subjectReaderMock, $storeManagerMock, $platformMock)
    {
        $orderAdapterMock = $this->getMockBuilder(OrderAdapterInterface::class)->getMock();
        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $storeMock = $this->getMockBuilder(StoreInterface::class)->getMock();

        $subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->willReturn($paymentDOMock);

        $paymentDOMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderAdapterMock);

        $orderAdapterMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn(1);

        $storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(1);

        $platformMock->expects($this->once())
            ->method('setDefaultWebsite')
            ->willReturn(null);
    }
}
