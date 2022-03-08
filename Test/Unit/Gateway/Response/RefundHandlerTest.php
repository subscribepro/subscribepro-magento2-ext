<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Swarming\SubscribePro\Gateway\Response\RefundHandler;

class RefundHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\RefundHandler
     */
    protected $refundHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp(): void
    {
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()->getMock();

        $this->refundHandler = new RefundHandler($this->subjectReaderMock);
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

        $this->refundHandler->handle($handlingSubject, $response);
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

    /**
     * @dataProvider handleDataProvider
     * @param array $handlingSubject
     * @param array $response
     * @param int $transactionId
     * @param bool $isTransactionClosed
     * @param array $transactionData
     * @param array $transactionDetails
     * @param bool $canRefund
     * @param bool $shouldCloseParentTransaction
     */
    public function testHandle(
        $handlingSubject,
        $response,
        $transactionId,
        $isTransactionClosed,
        $transactionData,
        $transactionDetails,
        $canRefund,
        $shouldCloseParentTransaction
    ) {
        $transactionMock = $this->getMockBuilder(TransactionInterface::class)->getMock();
        $transactionMock->expects($this->any())->method('getId')->willReturn($transactionId);
        $transactionMock->expects($this->once())->method('toArray')->willReturn($transactionData);

        $invoiceMock = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->once())->method('canRefund')->willReturn($canRefund);

        $creditMemoMock = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $creditMemoMock->expects($this->once())->method('getInvoice')->willReturn($invoiceMock);

        $paymentInfoMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoMock->expects($this->once())->method('setTransactionId')->with($transactionId);
        $paymentInfoMock->expects($this->once())->method('setIsTransactionClosed')->with($isTransactionClosed);
        $paymentInfoMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with($shouldCloseParentTransaction);
        $paymentInfoMock->expects($this->once())
            ->method('setTransactionAdditionalInfo')
            ->with(Transaction::RAW_DETAILS, $transactionDetails);
        $paymentInfoMock->expects($this->once())->method('getCreditmemo')->willReturn($creditMemoMock);

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

        $this->refundHandler->handle($handlingSubject, $response);
    }

    /**
     * @return array
     */
    public function handleDataProvider()
    {
        return [
            'Can refund' => [
                'handlingSubject' => ['subject'],
                'response' => ['response'],
                'transactionId' => 66623,
                'isTransactionClosed' => true,
                'transactionData' => [
                    TransactionInterface::GATEWAY_SPECIFIC_RESPONSE => 'response',
                    TransactionInterface::AMOUNT => 333,
                ],
                'transactionDetails' => [TransactionInterface::AMOUNT => 333],
                'canRefund' => true,
                'shouldCloseParentTransaction' => false
            ],
            'Can\'t refund' => [
                'handlingSubject' => ['subject'],
                'response' => ['response'],
                'transactionId' => 66623,
                'isTransactionClosed' => true,
                'transactionData' => [
                    TransactionInterface::GATEWAY_SPECIFIC_RESPONSE => 'response',
                    TransactionInterface::AMOUNT => 333,
                ],
                'transactionDetails' => [TransactionInterface::AMOUNT => 333],
                'canRefund' => false,
                'shouldCloseParentTransaction' => true
            ]
        ];
    }
}
