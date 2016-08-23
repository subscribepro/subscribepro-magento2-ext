<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Swarming\SubscribePro\Gateway\Response\CardDetailsHandler;

class CardDetailsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\CardDetailsHandler
     */
    protected $cardDetailsHandler;

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
        $this->configMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Config\Config')
            ->disableOriginalConstructor()->getMock();
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->cardDetailsHandler = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Response\CardDetailsHandler',
            [
                'config' => $this->configMock,
                'subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    /**
     * @expectedException \LogicException
     */
    public function testFailToHandleIfPaymentInfoNotValid()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];
        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
        
        $paymentInfoMock = $this->getMockBuilder('Magento\Payment\Model\InfoInterface')->getMock();
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

        $this->cardDetailsHandler->handle($handlingSubject, $response);
    }
    
    public function testHandle()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];

        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
        $transactionMock->expects($this->any())->method('getCreditcardLastDigits')->willReturn(4111);
        $transactionMock->expects($this->once())->method('getCreditcardMonth')->willReturn('05');
        $transactionMock->expects($this->once())->method('getCreditcardYear')->willReturn('2012');
        $transactionMock->expects($this->once())->method('getCreditcardType')->willReturn('visa');

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
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
        
        $this->configMock->expects($this->once())
            ->method('getMappedCcType')
            ->with('visa')
            ->willReturn('visa-type');

        $this->cardDetailsHandler->handle($handlingSubject, $response);
    }
}
