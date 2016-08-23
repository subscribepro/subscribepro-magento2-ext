<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use SubscribePro\Service\Transaction\TransactionInterface;

class OrderDataBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\OrderDataBuilder
     */
    protected $orderDataBuilder;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->orderDataBuilder = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Request\OrderDataBuilder',
            [
                'subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    public function testBuildWithoutBillingAddress() {
        $subject = ['subject'];
        $result = [
            TransactionInterface::AMOUNT => 1025,
            TransactionInterface::CURRENCY_CODE => 'US',
            TransactionInterface::ORDER_ID => 551,
            TransactionInterface::IP => '12.12.12.12',
            TransactionInterface::EMAIL => null
        ];

        $orderMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\OrderAdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())->method('getBillingAddress')->willReturn(null);
        $orderMock->expects($this->once())->method('getCurrencyCode')->willReturn('US');
        $orderMock->expects($this->once())->method('getOrderIncrementId')->willReturn(551);
        $orderMock->expects($this->once())->method('getRemoteIp')->willReturn('12.12.12.12');

        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readAmount')
            ->with($subject)
            ->willReturn(10.25);

        $this->assertEquals($result, $this->orderDataBuilder->build($subject));
    }
    
    public function testBuildWithBillingAddress() {
        $subject = ['subject'];
        $result = [
            TransactionInterface::AMOUNT => 1025,
            TransactionInterface::CURRENCY_CODE => 'US',
            TransactionInterface::ORDER_ID => 551,
            TransactionInterface::IP => '12.12.12.12',
            TransactionInterface::EMAIL => 'email'
        ];
        $billingAddressMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\AddressAdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->expects($this->once())->method('getEmail')->willReturn('email');

        $orderMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\OrderAdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->any())->method('getBillingAddress')->willReturn($billingAddressMock);
        $orderMock->expects($this->once())->method('getCurrencyCode')->willReturn('US');
        $orderMock->expects($this->once())->method('getOrderIncrementId')->willReturn(551);
        $orderMock->expects($this->once())->method('getRemoteIp')->willReturn('12.12.12.12');

        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readAmount')
            ->with($subject)
            ->willReturn(10.25);

        $this->assertEquals($result, $this->orderDataBuilder->build($subject));
    }
}
