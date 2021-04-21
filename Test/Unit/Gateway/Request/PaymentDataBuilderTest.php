<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class PaymentDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder
     */
    protected $paymentDataBuilder;

    protected function setUp(): void
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->paymentDataBuilder = new PaymentDataBuilder($this->subjectReaderMock);
    }

    public function testBuild()
    {
        $subject = ['subject'];
        $result = [
            PaymentDataBuilder::PAYMENT_METHOD_TOKEN => ['info'],
            VaultConfigProvider::IS_ACTIVE_CODE => 'active'
        ];

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
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

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->paymentDataBuilder->build($subject));
    }
}
