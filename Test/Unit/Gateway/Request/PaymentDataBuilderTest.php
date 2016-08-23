<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class PaymentDataBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder
     */
    protected $paymentDataBuilder;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->paymentDataBuilder = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder',
            [
                'subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    public function testBuild() {
        $subject = ['subject'];
        $result = [
            PaymentDataBuilder::PAYMENT_METHOD_TOKEN => ['info'],
            VaultConfigProvider::IS_ACTIVE_CODE => 'active'
        ];

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->at(0))
            ->method('getAdditionalInformation')
            ->with(PaymentDataBuilder::PAYMENT_METHOD_TOKEN)
            ->willReturn(['info']);
        $paymentInfoMock->expects($this->at(1))
            ->method('getAdditionalInformation')
            ->with(VaultConfigProvider::IS_ACTIVE_CODE)
            ->willReturn('active');

        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->paymentDataBuilder->build($subject));
    }
}
