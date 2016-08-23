<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Payment;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\CartInterface;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Magento\Payment\Model\Method\Adapter as MethodAdapter;
use Swarming\SubscribePro\Observer\Payment\Availability as PaymentAvailability;
use Magento\Checkout\Model\Session as CheckoutSession;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;

class Availability extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Payment\Availability
     */
    protected $paymentAvailability;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Checkout\Model\Session
     */
    protected $checkoutSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    protected function setUp()
    {
        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->paymentAvailability = new PaymentAvailability(
            $this->checkoutSessionMock,
            $this->quoteItemHelperMock
        );
    }

    public function testExecuteWithoutQuote()
    {
        $methodInstanceMock = $this->createMethodInstanceMock();
        $resultMock = $this->createResultMock();
        
        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('method_instance')
            ->willReturn($methodInstanceMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('result')
            ->willReturn($resultMock);
        $observerMock->expects($this->at(2))
            ->method('getData')
            ->with('quote')
            ->willReturn(null);
        
        $this->checkoutSessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);
        
        $resultMock->expects($this->never())->method('setData');

        $this->paymentAvailability->execute($observerMock);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $observerMock
     * @param \PHPUnit_Framework_MockObject_MockObject $quoteMock
     * @param \PHPUnit_Framework_MockObject_MockObject $resultMock
     * @param \PHPUnit_Framework_MockObject_MockObject $methodInstanceMock
     * @param string $methodCode
     * @param bool $isActiveNonSubscription
     * @param bool $hasQuoteSubscription
     * @param bool $isAvailable
     * @param bool $expectedIsAvailable
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $observerMock,
        $quoteMock,
        $resultMock,
        $methodInstanceMock,
        $methodCode,
        $isAvailable,
        $hasQuoteSubscription,
        $isActiveNonSubscription,
        $expectedIsAvailable
    ) {
        $methodInstanceMock->expects($this->once())->method('getCode')->willReturn($methodCode);
        $methodInstanceMock->expects($this->once())
            ->method('getConfigData')
            ->with(GatewayConfig::KEY_ACTIVE_NON_SUBSCRIPTION)
            ->willReturn($isActiveNonSubscription);

        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('method_instance')
            ->willReturn($methodInstanceMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('result')
            ->willReturn($resultMock);
        $observerMock->expects($this->at(2))
            ->method('getData')
            ->with('quote')
            ->willReturn($quoteMock);
        $observerMock->expects($this->at(3))
            ->method('getData')
            ->with('is_available')
            ->willReturn($isAvailable);
        
        $this->quoteItemHelperMock->expects($this->once())
            ->method('hasQuoteSubscription')
            ->with($quoteMock)
            ->willReturn($hasQuoteSubscription);

        $resultMock->expects($this->once())
            ->method('setData')
            ->with('is_available')
            ->willReturn($expectedIsAvailable);

        $this->paymentAvailability->execute($observerMock);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'No subscription: method code is not subscribe pro' => [
                'observerMock' => $this->createObserverMock(),
                'quoteMock' => $this->createQuoteMock(),
                'resultMock' => $this->createResultMock(),
                'methodInstanceMock' => $this->createMethodInstanceMock(),
                'methodCode' => 'code',
                'isAvailable' => false,
                'hasQuoteSubscription' => false,
                'isActiveNonSubscription' => true,
                'expectedIsAvailable' => false
            ],
            'No subscription: subscribePro method with active subscription config' => [
                'observerMock' => $this->createObserverMock(),
                'quoteMock' => $this->createQuoteMock(),
                'resultMock' => $this->createResultMock(),
                'methodInstanceMock' => $this->createMethodInstanceMock(),
                'methodCode' => ConfigProvider::CODE,
                'isAvailable' => true,
                'hasQuoteSubscription' => false,
                'isActiveNonSubscription' => false,
                'expectedIsAvailable' => false
            ],
            'With subscription: payment not available' => [
                'observerMock' => $this->createObserverMock(),
                'quoteMock' => $this->createQuoteMock(),
                'resultMock' => $this->createResultMock(),
                'methodInstanceMock' => $this->createMethodInstanceMock(),
                'methodCode' => 'not_subscribe_pro_code',
                'isAvailable' => true,
                'hasQuoteSubscription' => true,
                'isActiveNonSubscription' => true,
                'expectedIsAvailable' => false
            ],
            'With subscription: payment available' => [
                'observerMock' => $this->createObserverMock(),
                'quoteMock' => $this->createQuoteMock(),
                'resultMock' => $this->createResultMock(),
                'methodInstanceMock' => $this->createMethodInstanceMock(),
                'methodCode' => ConfigProvider::CODE,
                'isAvailable' => false,
                'hasQuoteSubscription' => true,
                'isActiveNonSubscription' => true,
                'expectedIsAvailable' => true
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Model\Method\Adapter
     */
    private function createMethodInstanceMock()
    {
        return $this->getMockBuilder(MethodAdapter::class)->disableOriginalConstructor()->getMock();
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartInterface
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(CartInterface::class)->getMock();
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject
     */
    private function createResultMock()
    {
        return $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();
    }
}
