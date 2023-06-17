<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Model\Config\General as GeneralConfig;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Observer\Checkout\SubmitAllAfter;

class SubmitAllAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Checkout\SubmitAllAfter
     */
    protected $checkoutSubmitAllAfter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Checkout\Model\Session
     */
    protected $checkoutSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Quote\SubscriptionCreator
     */
    protected $subscriptionCreatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->generalConfigMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->subscriptionCreatorMock = $this->getMockBuilder(SubscriptionCreator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->checkoutSubmitAllAfter = new SubmitAllAfter(
            $this->generalConfigMock,
            $this->checkoutSessionMock,
            $this->subscriptionCreatorMock,
            $this->loggerMock
        );
    }

    public function testExecuteIfModuleNotEnabled()
    {
        $websiteCode = 'code';

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->never())
            ->method('getPayment');

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->never())
            ->method('getCustomerId');

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('quote')
            ->willReturn($quoteMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('order')
            ->willReturn($orderMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(false);

        $this->subscriptionCreatorMock->expects($this->never())
            ->method('createSubscriptions');
        $this->checkoutSessionMock->expects($this->never())
            ->method('setData');

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    public function testExecuteIfPaymentNotSubscribePro()
    {
        $websiteCode = 'some_website_code';

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getMethod')
            ->willReturn('any_payment_except_subscribe_pro');

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->never())
            ->method('getCustomerId');

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock]
            ]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->never())
            ->method('createSubscriptions');
        $this->checkoutSessionMock->expects($this->never())
            ->method('setData');

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    public function testExecuteIfCustomerIdIsEmpty()
    {
        $websiteCode = 'some_website_code';

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getMethod')
            ->willReturn(ConfigProvider::CODE);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock]
            ]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->never())
            ->method('createSubscriptions');
        $this->checkoutSessionMock->expects($this->never())
            ->method('setData');

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    public function testExecuteIfFailToCreateSubscriptions()
    {
        $exception = new \Exception('error');

        $websiteCode = 'website_code';
        $customerId = 123;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getMethod')
            ->willReturn(ConfigProvider::CODE);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock]
            ]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->once())
            ->method('createSubscriptions')
            ->with($quoteMock, $orderMock)
            ->willThrowException($exception);

        $this->checkoutSessionMock->expects($this->never())->method('setData');

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    public function testExecute()
    {
        $websiteCode = 'website_code';
        $customerId = 321;
        $createdSubscriptionIds = [11, 12, 13];
        $failedSubscriptionsCount = 2;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $quoteMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getMethod')
            ->willReturn(ConfigProvider::CODE);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['quote', null, $quoteMock],
                ['order', null, $orderMock]
            ]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->once())
            ->method('createSubscriptions')
            ->with($quoteMock, $orderMock)
            ->willReturn([
                SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => $createdSubscriptionIds,
                SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => $failedSubscriptionsCount
            ]);

        $this->checkoutSessionMock->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                [SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, $createdSubscriptionIds],
                [SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, $failedSubscriptionsCount]
            );

        $this->loggerMock->expects($this->never())
            ->method('critical');

        $this->checkoutSubmitAllAfter->execute($observerMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Api\Data\OrderInterface
     */
    private function createOrderMock()
    {
        return $this->getMockBuilder(OrderInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Api\Data\OrderPaymentInterface
     */
    private function createPaymentMock()
    {
        return $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\Store
     */
    private function createStoreMock()
    {
        return $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\WebsiteInterface
     */
    private function createWebsiteMock()
    {
        return $this->getMockBuilder(WebsiteInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getCustomerId', '__wakeup'])
            ->getMock();
    }
}
