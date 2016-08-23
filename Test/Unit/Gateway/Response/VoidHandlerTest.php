<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Magento\Sales\Model\Order\Payment\Transaction;
use SubscribePro\Service\Transaction\TransactionInterface;

class VoidHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\VoidHandler
     */
    protected $voidHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->voidHandler = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Response\VoidHandler',
            [
                'subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    /**
     * @dataProvider handleWithInvalidPaymentDataProvider
     * @param mixed $payment
     */
    public function testHandleWithInvalidPayment($payment)
    {
        $handlingSubject = ['subject'];
        $response = ['response'];
        
        $paymentDOMock = $this->getMockBuilder('Magento\Payment\Gateway\Data\PaymentDataObjectInterface')->getMock();
        $paymentDOMock->expects($this->once())->method('getPayment')->willReturn($payment);

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->with($handlingSubject)
            ->willReturn($paymentDOMock);
        
        $this->subjectReaderMock->expects($this->never())->method('readTransaction');

        $this->voidHandler->handle($handlingSubject, $response);
    }

    /**
     * @return array
     */
    public function handleWithInvalidPaymentDataProvider()
    {
        return [
            'payment is null' => ['payment' => null],
            'payment is not object' => ['payment' => 'string'],
            'payment is not instance of payment' => ['payment' => new \ArrayObject()],
        ];
    }
    
    public function testHandle()
    {
        $handlingSubject = ['subject'];
        $response = ['response'];
        $transactionData = [
            TransactionInterface::GATEWAY_SPECIFIC_RESPONSE => 'response',
            TransactionInterface::AMOUNT => 333,
        ];
        $transactionDetails = [TransactionInterface::AMOUNT => 333];

        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
        $transactionMock->expects($this->any())->method('getId')->willReturn(313);
        $transactionMock->expects($this->once())->method('toArray')->willReturn($transactionData);

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('setTransactionId')->with(313);
        $paymentInfoMock->expects($this->once())->method('setIsTransactionClosed')->with(true);
        $paymentInfoMock->expects($this->once())->method('setShouldCloseParentTransaction')->with(true);
        $paymentInfoMock->expects($this->once())
            ->method('setTransactionAdditionalInfo')
            ->with(Transaction::RAW_DETAILS, $transactionDetails);
        
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
        
        $this->voidHandler->handle($handlingSubject, $response);
    }
}
