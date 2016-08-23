<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

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
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->customerDataBuilder = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Request\CustomerDataBuilder',
            [
                'subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    public function testBuild() {
        $subject = ['subject'];
        $customerId = 123;
        $result = [
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => 123,
        ];

        $orderMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\OrderAdapterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getOrder')->willReturn($orderMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->customerDataBuilder->build($subject));
    }
}
