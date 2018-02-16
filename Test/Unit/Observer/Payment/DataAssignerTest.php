<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Payment;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;
use Swarming\SubscribePro\Observer\Payment\DataAssigner as PaymentDataAssigner;
use Magento\Payment\Model\InfoInterface as PaymentInfoInterface;

class DataAssignerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Payment\DataAssigner
     */
    protected $paymentDataAssigner;

    protected function setUp()
    {
        $this->paymentDataAssigner = new PaymentDataAssigner();
    }

    public function testExecuteIfAdditionalDataNotArray()
    {
        $dataMock = $this->createDataMock();
        $dataMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn('not array');
        
        $eventMock = $this->createEventMock();
        $eventMock->expects($this->once())
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::DATA_CODE)
            ->willReturn($dataMock);
        
        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);
        
        $this->paymentDataAssigner->execute($observerMock);
    }

    public function testExecute()
    {
        $additionalInfo = [
            PaymentDataBuilder::PAYMENT_METHOD_TOKEN => 'payment token',
            'some_key' => 'value',
        ];
        
        $dataMock = $this->createDataMock();
        $dataMock->expects($this->once())
            ->method('getData')
            ->with(PaymentInterface::KEY_ADDITIONAL_DATA)
            ->willReturn($additionalInfo);
        
        $paymentInfoMock = $this->createPaymentInfoMock();
        $paymentInfoMock->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(PaymentDataBuilder::PAYMENT_METHOD_TOKEN, 'payment token');
        
        $eventMock = $this->createEventMock();
        $eventMock->expects($this->at(0))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::DATA_CODE)
            ->willReturn($dataMock);
        $eventMock->expects($this->at(1))
            ->method('getDataByKey')
            ->with(AbstractDataAssignObserver::MODEL_CODE)
            ->willReturn($paymentInfoMock);
        
        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($eventMock);
        
        $this->paymentDataAssigner->execute($observerMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Model\InfoInterface
     */
    private function createPaymentInfoMock()
    {
        return $this->getMockBuilder(PaymentInfoInterface::class)->getMock();
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event
     */
    private function createEventMock()
    {
        return $this->getMockBuilder(Event::class)->getMock();
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject
     */
    private function createDataMock()
    {
        return $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();
    }
}
