<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Magento\Vault\Model\Ui\VaultConfigProvider;

class VaultDetailsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\VaultDetailsHandler
     */
    protected $vaultDetailsHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\Data\PaymentTokenInterfaceFactory
     */
    protected $paymentTokenFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();
        $this->paymentTokenFactoryMock = $this->getMockBuilder('Magento\Vault\Api\Data\PaymentTokenInterfaceFactory')
            ->disableOriginalConstructor()->getMock();
        $this->paymentExtensionFactoryMock = $this->getMockBuilder('Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory')
            ->disableOriginalConstructor()->getMock();
        $this->vaultHelperMock = $this->getMockBuilder('Swarming\SubscribePro\Helper\Vault')
            ->disableOriginalConstructor()->getMock();
        $this->configMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Config\Config')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->vaultDetailsHandler = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Response\VaultDetailsHandler',
            [
                'subjectReader' => $this->subjectReaderMock,
                'paymentTokenFactory' => $this->paymentTokenFactoryMock,
                'paymentExtensionFactory' => $this->paymentExtensionFactoryMock,
                'vaultHelper' => $this->vaultHelperMock,
                'config' => $this->configMock,
            ]
        );
    }

    public function testHandleIfPaymentNotActive()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];

        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('getAdditionalInformation')
            ->with(VaultConfigProvider::IS_ACTIVE_CODE)
            ->willReturn(false);
        $paymentInfoMock->expects($this->never())->method('getExtensionAttributes');
        
        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);
        
        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($handlingSubject)
            ->willReturn($paymentDOMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readTransaction')
            ->with($response)
            ->willReturn($transactionMock);

        $this->vaultDetailsHandler->handle($handlingSubject, $response);
    }
    
    public function testHandleWithoutExtensionAttributes()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];
        $expiresAt = '2020-04-01';
        $tokenDetails = ['token details'];

        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
        $transactionMock->expects($this->once())->method('getRefPaymentProfileId')->willReturn(1231);
        $transactionMock->expects($this->any())->method('getCreditcardYear')->willReturn('2020');
        $transactionMock->expects($this->any())->method('getCreditcardMonth')->willReturn('04');
        $transactionMock->expects($this->once())->method('getCreditcardType')->willReturn('visa');
        $transactionMock->expects($this->once())->method('getCreditcardLastDigits')->willReturn(4444);

        $paymentTokenMock = $this->getMockBuilder('Magento\Vault\Api\Data\PaymentTokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenMock->expects($this->once())->method('setGatewayToken')->with(1231);
        $paymentTokenMock->expects($this->once())->method('setExpiresAt')->with($expiresAt);
        $paymentTokenMock->expects($this->once())->method('setTokenDetails')->with($tokenDetails);

        $extensionAttributesMock = $this->getMockBuilder('Magento\Sales\Api\Data\OrderPaymentExtensionInterface')->getMock();
        $extensionAttributesMock->expects($this->once())
            ->method('setVaultPaymentToken')
            ->with($paymentTokenMock);

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('getAdditionalInformation')
            ->with(VaultConfigProvider::IS_ACTIVE_CODE)
            ->willReturn(true);
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $paymentInfoMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->willReturn($extensionAttributesMock);
        
        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);
        
        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($handlingSubject)
            ->willReturn($paymentDOMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readTransaction')
            ->with($response)
            ->willReturn($transactionMock);

        $this->paymentTokenFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($paymentTokenMock);
        
        $this->paymentExtensionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($extensionAttributesMock);

        $this->vaultHelperMock->expects($this->once())
            ->method('getExpirationDate')
            ->with('2020', '04')
            ->willReturn($expiresAt);
        $this->vaultHelperMock->expects($this->once())
            ->method('getTokenDetails')
            ->with('visa', 4444, '04', '2020')
            ->willReturn($tokenDetails);

        $this->vaultDetailsHandler->handle($handlingSubject, $response);
    }
    
    public function testHandleWithExtensionAttributes()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];
        $expiresAt = '2020-04-01';
        $tokenDetails = ['token details'];

        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
        $transactionMock->expects($this->once())->method('getRefPaymentProfileId')->willReturn(1231);
        $transactionMock->expects($this->any())->method('getCreditcardYear')->willReturn('2020');
        $transactionMock->expects($this->any())->method('getCreditcardMonth')->willReturn('04');
        $transactionMock->expects($this->once())->method('getCreditcardType')->willReturn('visa');
        $transactionMock->expects($this->once())->method('getCreditcardLastDigits')->willReturn(4444);

        $paymentTokenMock = $this->getMockBuilder('Magento\Vault\Api\Data\PaymentTokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentTokenMock->expects($this->once())->method('setGatewayToken')->with(1231);
        $paymentTokenMock->expects($this->once())->method('setExpiresAt')->with($expiresAt);
        $paymentTokenMock->expects($this->once())->method('setTokenDetails')->with($tokenDetails);

        $extensionAttributesMock = $this->getMockBuilder('Magento\Sales\Api\Data\OrderPaymentExtensionInterface')->getMock();
        $extensionAttributesMock->expects($this->once())
            ->method('setVaultPaymentToken')
            ->with($paymentTokenMock);

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('getAdditionalInformation')
            ->with(VaultConfigProvider::IS_ACTIVE_CODE)
            ->willReturn(true);
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);
        
        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($paymentInfoMock);
        
        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($handlingSubject)
            ->willReturn($paymentDOMock);

        $this->subjectReaderMock->expects($this->once())
            ->method('readTransaction')
            ->with($response)
            ->willReturn($transactionMock);

        $this->paymentTokenFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($paymentTokenMock);

        $this->vaultHelperMock->expects($this->once())
            ->method('getExpirationDate')
            ->with('2020', '04')
            ->willReturn($expiresAt);
        $this->vaultHelperMock->expects($this->once())
            ->method('getTokenDetails')
            ->with('visa', 4444, '04', '2020')
            ->willReturn($tokenDetails);

        $this->vaultDetailsHandler->handle($handlingSubject, $response);
    }
}
