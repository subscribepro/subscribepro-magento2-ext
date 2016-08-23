<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Observer\Checkout\AllSubmitAfter;
use Swarming\SubscribePro\Model\Config\General as ConfigGeneral;

class AllSubmitAfterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Checkout\AllSubmitAfter
     */
    protected $allSubmitAfter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $configGeneralMock;

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

    protected function setUp()
    {
        $this->configGeneralMock = $this->getMockBuilder(ConfigGeneral::class)
            ->disableOriginalConstructor()->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $this->subscriptionCreatorMock = $this->getMockBuilder(SubscriptionCreator::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->allSubmitAfter = new AllSubmitAfter(
            $this->configGeneralMock,
            $this->checkoutSessionMock,
            $this->subscriptionCreatorMock,
            $this->loggerMock
        );
    }

    public function testExecuteIfSubscribeProNotEnabled()
    {
        $websiteCode = 'code';
        $orderMock = $this->createOrderMock();

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

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('quote')
            ->willReturn($quoteMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('order')
            ->willReturn($orderMock);

        $this->configGeneralMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(false);
        
        $this->subscriptionCreatorMock->expects($this->never())->method('createSubscriptions');
        $this->checkoutSessionMock->expects($this->never())->method('setData');

        $this->allSubmitAfter->execute($observerMock);
    }

    public function testExecuteIfFailToCreateSubscriptions()
    {
        $exception = new \Exception('error');

        $websiteCode = 'code';
        $orderMock = $this->createOrderMock();

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

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('quote')
            ->willReturn($quoteMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('order')
            ->willReturn($orderMock);

        $this->configGeneralMock->expects($this->once())
            ->method('isEnabled')
            ->with($websiteCode)
            ->willReturn(true);

        $this->subscriptionCreatorMock->expects($this->once())
            ->method('createSubscriptions')
            ->with($quoteMock, $orderMock)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->checkoutSessionMock->expects($this->never())->method('setData');

        $this->allSubmitAfter->execute($observerMock);
    }

    public function testExecute()
    {
        $createdSubscriptionIds = [11,12,13];
        $failedSubscriptionsCount = 2;
        $websiteCode = 'code';

        $orderMock = $this->createOrderMock();

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

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->at(0))
            ->method('getData')
            ->with('quote')
            ->willReturn($quoteMock);
        $observerMock->expects($this->at(1))
            ->method('getData')
            ->with('order')
            ->willReturn($orderMock);

        $this->configGeneralMock->expects($this->once())
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

        $this->checkoutSessionMock->expects($this->at(0))
            ->method('setData')
            ->with(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, $createdSubscriptionIds);
        $this->checkoutSessionMock->expects($this->at(1))
            ->method('setData')
            ->with(SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, $failedSubscriptionsCount);

        $this->allSubmitAfter->execute($observerMock);
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
        return $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
    }
}
