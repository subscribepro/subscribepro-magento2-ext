<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Request;

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
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->vaultDataBuilder = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Request\VaultDataBuilder',
            [
                'subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The vault is not found.
     * @dataProvider failToBuildWithEmptyVaultDataProvider
     * @param null|\PHPUnit_Framework_MockObject_MockObject $extensionAttributes
     */
    public function testFailToBuildWithEmptyVault($extensionAttributes) {
        $subject = ['subject'];

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        
        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
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
        $extensionAttributes = $this->getMockBuilder('Magento\Sales\Api\Data\OrderPaymentExtensionInterface')->getMock();
        $extensionAttributes->expects($this->any())->method('getVaultPaymentToken')->willReturn(null);
        
        return [
            'without extension attributes' => ['extensionAttributes' => null],
            'with extension attributes' => ['extensionAttributes' => $extensionAttributes],
        ];
    }

    public function testBuild() {
        $subject = ['subject'];
        $result = [VaultDataBuilder::PAYMENT_PROFILE_ID => 'token'];

        $paymentTokenMock = $this->getMockBuilder('Magento\Vault\Api\Data\PaymentTokenInterface')->getMock();
        $paymentTokenMock->expects($this->once())->method('getGatewayToken')->willReturn('token');

        $extensionAttributes = $this->getMockBuilder('Magento\Sales\Api\Data\OrderPaymentExtensionInterface')->getMock();
        $extensionAttributes->expects($this->any())
            ->method('getVaultPaymentToken')
            ->willReturn($paymentTokenMock);

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);

        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($subject)
            ->willReturn($paymentDOMock);

        $this->assertEquals($result, $this->vaultDataBuilder->build($subject));
    }
}
