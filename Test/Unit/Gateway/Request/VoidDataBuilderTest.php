<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Request\VoidDataBuilder;

class VoidDataBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\VoidDataBuilder
     */
    protected $voidDataBuilder;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->voidDataBuilder = new VoidDataBuilder($this->subjectReaderMock);
    }

    public function testBuild() {
        $subject = ['subject'];
        $result = [TransactionInterface::REF_TRANSACTION_ID => 123];

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('getParentTransactionId')->willReturn(123);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->voidDataBuilder->build($subject));
    }
}
