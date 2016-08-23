<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;

class VaultDataBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Request\VaultDataBuilder
     */
    protected $vaultDataBuilder;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->vaultDataBuilder = new VaultDataBuilder($this->subjectReaderMock);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The vault is not found.
     * @dataProvider failToBuildWithEmptyVaultDataProvider
     * @param null|\PHPUnit_Framework_MockObject_MockObject $extensionAttributes
     */
    public function testFailToBuildWithEmptyVault($extensionAttributes) {
        $subject = ['subject'];

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        
        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->vaultDataBuilder->build($subject);
    }

    /**
     * @return array
     */
    public function failToBuildWithEmptyVaultDataProvider()
    {
        $extensionAttributes = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->setMethods(['setVaultPaymentToken', 'getVaultPaymentToken'])
            ->getMock();
        $extensionAttributes->expects($this->any())->method('getVaultPaymentToken')->willReturn(null);
        
        return [
            'Without extension attributes' => ['extensionAttributes' => null],
            'With extension attributes' => ['extensionAttributes' => $extensionAttributes],
        ];
    }

    public function testBuild() {
        $subject = ['subject'];
        $result = [VaultDataBuilder::PAYMENT_PROFILE_ID => 'token'];

        $paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)->getMock();
        $paymentTokenMock->expects($this->once())->method('getGatewayToken')->willReturn('token');

        $extensionAttributes = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->setMethods(['setVaultPaymentToken', 'getVaultPaymentToken'])
            ->getMock();
        $extensionAttributes->expects($this->any())
            ->method('getVaultPaymentToken')
            ->willReturn($paymentTokenMock);

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->vaultDataBuilder->build($subject));
    }
}
