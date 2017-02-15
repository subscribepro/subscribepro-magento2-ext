<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use \Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Response\VaultDetailsHandler;
use Swarming\SubscribePro\Helper\Vault as SubscribeProVaultHelper;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;

class VaultDetailsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\VaultDetailsHandler
     */
    protected $vaultDetailsHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Model\CreditCardTokenFactory
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
    protected $gatewayConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();
        $this->paymentTokenFactoryMock = $this->getMockBuilder(CreditCardTokenFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getType'])->getMock();
        $this->paymentExtensionFactoryMock = $this->getMockBuilder(OrderPaymentExtensionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])->getMock();
        $this->vaultHelperMock = $this->getMockBuilder(SubscribeProVaultHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->gatewayConfigMock = $this->getMockBuilder(GatewayConfig::class)
            ->disableOriginalConstructor()->getMock();

        $this->vaultDetailsHandler = new VaultDetailsHandler(
            $this->paymentTokenFactoryMock,
            $this->paymentExtensionFactoryMock,
            $this->vaultHelperMock,
            $this->gatewayConfigMock,
            $this->subjectReaderMock
        );
    }

    public function testHandleIfPaymentNotActive()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];

        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('getAdditionalInformation')
            ->with(VaultConfigProvider::IS_ACTIVE_CODE)
            ->willReturn(false);
        $paymentInfoMock->expects($this->never())->method('getExtensionAttributes');
        
        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
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

        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();
        $transactionMock->expects($this->once())->method('getRefPaymentProfileId')->willReturn(1231);
        $transactionMock->expects($this->any())->method('getCreditcardYear')->willReturn('2020');
        $transactionMock->expects($this->any())->method('getCreditcardMonth')->willReturn('04');
        $transactionMock->expects($this->once())->method('getCreditcardType')->willReturn('visa');
        $transactionMock->expects($this->once())->method('getCreditcardLastDigits')->willReturn(4444);

        $paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)->getMock();
        $paymentTokenMock->expects($this->once())->method('setGatewayToken')->with(1231);
        $paymentTokenMock->expects($this->once())->method('setExpiresAt')->with($expiresAt);
        $paymentTokenMock->expects($this->once())->method('setTokenDetails')->with($tokenDetails);

        $extensionAttributesMock = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->setMethods(['setVaultPaymentToken', 'getVaultPaymentToken'])
            ->getMock();
        $extensionAttributesMock->expects($this->once())
            ->method('setVaultPaymentToken')
            ->with($paymentTokenMock);

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
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
        
        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
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

        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();
        $transactionMock->expects($this->once())->method('getRefPaymentProfileId')->willReturn(1231);
        $transactionMock->expects($this->any())->method('getCreditcardYear')->willReturn('2020');
        $transactionMock->expects($this->any())->method('getCreditcardMonth')->willReturn('04');
        $transactionMock->expects($this->once())->method('getCreditcardType')->willReturn('visa');
        $transactionMock->expects($this->once())->method('getCreditcardLastDigits')->willReturn(4444);

        $paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)->getMock();
        $paymentTokenMock->expects($this->once())->method('setGatewayToken')->with(1231);
        $paymentTokenMock->expects($this->once())->method('setExpiresAt')->with($expiresAt);
        $paymentTokenMock->expects($this->once())->method('setTokenDetails')->with($tokenDetails);

        $extensionAttributesMock = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->setMethods(['setVaultPaymentToken', 'getVaultPaymentToken'])
            ->getMock();
        $extensionAttributesMock->expects($this->once())
            ->method('setVaultPaymentToken')
            ->with($paymentTokenMock);

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('getAdditionalInformation')
            ->with(VaultConfigProvider::IS_ACTIVE_CODE)
            ->willReturn(true);
        $paymentInfoMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);
        
        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
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
