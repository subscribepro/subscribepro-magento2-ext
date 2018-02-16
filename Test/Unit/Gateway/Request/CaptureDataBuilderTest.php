<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Request\CaptureDataBuilder;

class CaptureDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\CaptureDataBuilder
     */
    protected $captureDataBuilder;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->captureDataBuilder = new CaptureDataBuilder($this->subjectReaderMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Parent transaction is not found.
     */
    public function testFailToBuildWithoutParentTransaction() {
        $subject = ['subject'];
        
        $orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getParentTransactionId')
            ->willReturn(null);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);
        
        $this->captureDataBuilder->build($subject);
    }
    
    public function testBuildWithInvalidAmount() {
        $subject = ['subject'];
        $transactionId = 131;
        $result = [
            TransactionInterface::REF_TRANSACTION_ID => 131,
            TransactionInterface::AMOUNT => null,
            TransactionInterface::CURRENCY_CODE => null
        ];
        
        $orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->never())->method('getCurrencyCode');
        
        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getParentTransactionId')
            ->willReturn($transactionId);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);
        
        $this->subjectReaderMock->expects($this->once())
            ->method('readAmount')
            ->with($subject)
            ->willThrowException(new \InvalidArgumentException());

        $this->assertEquals($result, $this->captureDataBuilder->build($subject));
    }
    
    public function testBuild() {
        $subject = ['subject'];
        $transactionId = 131;
        $amount = 1234;
        $currencyCode = 'US';
        $result = [
            TransactionInterface::REF_TRANSACTION_ID => 131,
            TransactionInterface::AMOUNT => '123400.00',
            TransactionInterface::CURRENCY_CODE => 'US'
        ];
        
        $orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getCurrencyCode')->willReturn($currencyCode);
        
        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getParentTransactionId')
            ->willReturn($transactionId);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);
        
        $this->subjectReaderMock->expects($this->once())
            ->method('readAmount')
            ->with($subject)
            ->willReturn($amount);

        $this->assertEquals($result, $this->captureDataBuilder->build($subject));
    }
}
