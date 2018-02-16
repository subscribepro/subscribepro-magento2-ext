<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Response\CardDetailsHandler;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;
use Magento\Payment\Model\InfoInterface as PaymentInfoInterface;

class CardDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\CardDetailsHandler
     */
    protected $cardDetailsHandler;

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
        $this->gatewayConfigMock = $this->getMockBuilder(GatewayConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->cardDetailsHandler = new CardDetailsHandler($this->gatewayConfigMock, $this->subjectReaderMock);
    }

    /**
     * @expectedException \LogicException
     */
    public function testFailToHandleIfPaymentInfoNotValid()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];
        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();
        
        $paymentInfoMock = $this->getMockBuilder(PaymentInfoInterface::class)->getMock();
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

        $this->cardDetailsHandler->handle($handlingSubject, $response);
    }
    
    public function testHandle()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];

        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();
        $transactionMock->expects($this->any())->method('getCreditcardLastDigits')->willReturn(4111);
        $transactionMock->expects($this->once())->method('getCreditcardMonth')->willReturn('05');
        $transactionMock->expects($this->once())->method('getCreditcardYear')->willReturn('2012');
        $transactionMock->expects($this->once())->method('getCreditcardType')->willReturn('visa');

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('setCcLast4')->with(4111);
        $paymentInfoMock->expects($this->once())->method('setCcExpMonth')->with('05');
        $paymentInfoMock->expects($this->once())->method('setCcExpYear')->with('2012');
        $paymentInfoMock->expects($this->once())->method('setCcType')->with('visa-type');
        $paymentInfoMock->expects($this->at(4))
            ->method('setAdditionalInformation')
            ->with(CardDetailsHandler::CARD_TYPE);
        $paymentInfoMock->expects($this->at(5))
            ->method('setAdditionalInformation')
            ->with(CardDetailsHandler::CARD_NUMBER, 'xxxx-4111');
        
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
        
        $this->gatewayConfigMock->expects($this->once())
            ->method('getMappedCcType')
            ->with('visa')
            ->willReturn('visa-type');

        $this->cardDetailsHandler->handle($handlingSubject, $response);
    }
}
