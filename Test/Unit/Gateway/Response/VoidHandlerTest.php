<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Response\VoidHandler;

class VoidHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\VoidHandler
     */
    protected $voidHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp(): void
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->voidHandler = new VoidHandler($this->subjectReaderMock);
    }

    /**
     * @dataProvider handleWithInvalidPaymentDataProvider
     * @param mixed $payment
     */
    public function testHandleWithInvalidPayment($payment)
    {
        $handlingSubject = ['subject'];
        $response = ['response'];

        $paymentDOMock = $this->getMockBuilder(PaymentDataObjectInterface::class)->getMock();
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
            'Payment is null' => ['payment' => null],
            'Payment is not object' => ['payment' => 'string'],
            'Payment is not instance of payment' => ['payment' => new \stdClass()],
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

        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();
        $transactionMock->expects($this->any())->method('getId')->willReturn(313);
        $transactionMock->expects($this->once())->method('toArray')->willReturn($transactionData);

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('setTransactionId')->with(313);
        $paymentInfoMock->expects($this->once())->method('setIsTransactionClosed')->with(true);
        $paymentInfoMock->expects($this->once())->method('setShouldCloseParentTransaction')->with(true);
        $paymentInfoMock->expects($this->once())
            ->method('setTransactionAdditionalInfo')
            ->with(Transaction::RAW_DETAILS, $transactionDetails);

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

        $this->voidHandler->handle($handlingSubject, $response);
    }
}
