<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\Method\Adapter as MethodAdapter;
use Magento\Quote\Api\Data\CartInterface;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Helper\Quote as QuoteHelper;
use Swarming\SubscribePro\Observer\Payment\Availability as PaymentAvailability;

class Availability extends \PHPUnit\Framework\TestCase
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
    protected $quoteHelperMock;

    protected function setUp(): void
    {
        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteHelperMock = $this->getMockBuilder(QuoteHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->paymentAvailability = new PaymentAvailability(
            $this->checkoutSessionMock,
            $this->quoteHelperMock
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
     * @param string $methodCode
     * @param bool $isActiveNonSubscription
     * @param bool $hasSubscription
     * @param bool $isAvailable
     * @param bool $expectedIsAvailable
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $methodCode,
        $isAvailable,
        $hasSubscription,
        $isActiveNonSubscription,
        $expectedIsAvailable
    ) {
        $quoteMock = $this->createQuoteMock();

        $methodInstanceMock = $this->createMethodInstanceMock();
        $methodInstanceMock->expects($this->once())->method('getCode')->willReturn($methodCode);
        $methodInstanceMock->expects($this->once())
            ->method('getConfigData')
            ->with(GatewayConfig::KEY_ACTIVE_NON_SUBSCRIPTION)
            ->willReturn($isActiveNonSubscription);

        $resultMock = $this->createResultMock();
        $resultMock->expects($this->once())
            ->method('getData')
            ->with('is_available')
            ->willReturn($isAvailable);
        $resultMock->expects($this->once())
            ->method('setData')
            ->with('is_available', $expectedIsAvailable);

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
            ->willReturn($quoteMock);

        $this->quoteHelperMock->expects($this->once())
            ->method('hasSubscription')
            ->with($quoteMock)
            ->willReturn($hasSubscription);

        $this->paymentAvailability->execute($observerMock);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'No subscription: method code is not subscribe pro' => [
                'methodCode' => 'code',
                'isAvailable' => false,
                'hasSubscription' => false,
                'isActiveNonSubscription' => true,
                'expectedIsAvailable' => false
            ],
            'No subscription: subscribePro method with active subscription config' => [
                'methodCode' => ConfigProvider::CODE,
                'isAvailable' => true,
                'hasSubscription' => false,
                'isActiveNonSubscription' => false,
                'expectedIsAvailable' => false
            ],
            'With subscription:not available:payment not available' => [
                'methodCode' => ConfigProvider::CODE,
                'isAvailable' => false,
                'hasSubscription' => true,
                'isActiveNonSubscription' => true,
                'expectedIsAvailable' => false
            ],
            'With subscription:payment method not subscribe pro:payment not available' => [
                'methodCode' => 'not_subscribe_pro_code',
                'isAvailable' => true,
                'hasSubscription' => true,
                'isActiveNonSubscription' => true,
                'expectedIsAvailable' => false
            ],
            'With subscription: payment available' => [
                'methodCode' => ConfigProvider::CODE,
                'isAvailable' => true,
                'hasSubscription' => true,
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
