<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use SubscribePro\Service\Transaction\TransactionInterface;

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
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->voidDataBuilder = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Request\VoidDataBuilder',
            [
                'subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    public function testBuild() {
        $subject = ['subject'];
        $result = [TransactionInterface::REF_TRANSACTION_ID => 123];

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('getParentTransactionId')->willReturn(123);

        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->voidDataBuilder->build($subject));
    }
}
