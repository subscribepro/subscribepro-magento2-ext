<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Api\Data\AddressInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Request\AddressDataBuilder;

class AddressDataReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\AddressDataBuilder
     */
    protected $addressDataBuilder;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->addressDataBuilder = new AddressDataBuilder($this->subjectReaderMock);
    }

    public function testBuildWithoutBillingAddress()
    {
        $subject = ['subject'];
        $orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getBillingAddress')->willReturn(null);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals([], $this->addressDataBuilder->build($subject));
    }

    public function testBuildWithBillingAddress()
    {
        $subject = ['subject'];
        $result = [
            PaymentProfileInterface::BILLING_ADDRESS => [
                AddressInterface::FIRST_NAME => 'John',
                AddressInterface::LAST_NAME => 'Snow',
                AddressInterface::COMPANY => 'Starcraft',
                AddressInterface::STREET1 => 'Some street',
                AddressInterface::STREET2 => 'Metal st.',
                AddressInterface::CITY => 'New York',
                AddressInterface::REGION => 'ABB',
                AddressInterface::POSTCODE => '101 01',
                AddressInterface::COUNTRY => 'USA'
            ]
        ];
        $billingAddressMock = $this->getMockBuilder(AddressAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $billingAddressMock->expects($this->once())->method('getFirstname')->willReturn('John');
        $billingAddressMock->expects($this->once())->method('getLastname')->willReturn('Snow');
        $billingAddressMock->expects($this->once())->method('getCompany')->willReturn('Starcraft');
        $billingAddressMock->expects($this->once())->method('getStreetLine1')->willReturn('Some street');
        $billingAddressMock->expects($this->once())->method('getStreetLine2')->willReturn('Metal st.');
        $billingAddressMock->expects($this->once())->method('getCity')->willReturn('New York');
        $billingAddressMock->expects($this->once())->method('getRegionCode')->willReturn('ABB');
        $billingAddressMock->expects($this->once())->method('getPostcode')->willReturn('101 01');
        $billingAddressMock->expects($this->once())->method('getCountryId')->willReturn('USA');

        $orderMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\OrderAdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getBillingAddress')->willReturn($billingAddressMock);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->addressDataBuilder->build($subject));
    }
}
