<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Response;

use Magento\Sales\Model\Order\Payment\Transaction;
use SubscribePro\Service\Transaction\TransactionInterface;

class RefundHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Response\RefundHandler
     */
    protected $refundHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Helper\SubjectReader')
            ->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->refundHandler = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Response\RefundHandler',
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

        $this->refundHandler->handle($handlingSubject, $response);
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
        $transactionMock = $this->getMockBuilder('SubscribePro\Service\Transaction\TransactionInterface')->getMock();
        $transactionMock->expects($this->any())->method('getId')->willReturn($transactionId);
        $transactionMock->expects($this->once())->method('toArray')->willReturn($transactionData);

        $invoiceMock = $this->getMockBuilder('Magento\Sales\Model\Order\Invoice')
            ->disableOriginalConstructor()
            ->getMock();
        $invoiceMock->expects($this->once())->method('canRefund')->willReturn($canRefund);

        $creditMemoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Creditmemo')
            ->disableOriginalConstructor()
            ->getMock();
        $creditMemoMock->expects($this->once())->method('getInvoice')->willReturn($invoiceMock);

        $paymentInfoMock = $this->getMockBuilder('Magento\Sales\Model\Order\Payment')
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
        
        $this->refundHandler->handle($handlingSubject, $response);
    }

    /**
     * @return array
     */
    public function handleDataProvider()
    {
        return [
            'can refund' => [
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
            'can\'t refund' => [
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
