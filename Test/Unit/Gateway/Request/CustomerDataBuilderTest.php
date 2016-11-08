<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
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
        $customerEmail = 'email';
        $result = [
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => 123,
            PaymentProfileInterface::CUSTOMER_EMAIL => $customerEmail
        ];

        $billingAddressMock = $this->getMockBuilder(AddressAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $billingAddressMock->expects($this->once())->method('getEmail')->willReturn($customerEmail);

        $orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getBillingAddress')->willReturn($billingAddressMock);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->customerDataBuilder->build($subject));
    }
}
