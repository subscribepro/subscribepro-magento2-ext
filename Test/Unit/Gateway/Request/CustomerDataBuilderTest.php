<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Request\CustomerDataBuilder;

class CustomerDataBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\CustomerDataBuilder
     */
    protected $customerDataBuilder;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->customerDataBuilder = new CustomerDataBuilder($this->subjectReaderMock);
    }

    public function testBuild() {
        $subject = ['subject'];
        $customerId = 123;
        $result = [
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => 123,
        ];

        $orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->customerDataBuilder->build($subject));
    }
}
