<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Service;

use SubscribePro\Service\Subscription\SubscriptionService;
use Swarming\SubscribePro\Model\Subscription as SubscriptionModel;
use Swarming\SubscribePro\Platform\Service\Subscription;

class SubscriptionTest extends AbstractService
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $subscriptionService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Subscription\SubscriptionService
     */
    protected $subscriptionPlatformService;

    protected function setUp()
    {
        $this->platformMock = $this->createPlatformMock();
        $this->subscriptionPlatformService = $this->getMockBuilder(SubscriptionService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriptionService = new Subscription($this->platformMock, $this->name);
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @dataProvider createSubscriptionDataProvider
     */
    public function testCreateSubscription($websiteId, $expectedWebsiteId)
    {
        $subscriptionMock = $this->createSubscriptionMock();
        
        $this->initService($this->subscriptionPlatformService, $expectedWebsiteId);
        $this->subscriptionPlatformService->expects($this->once())
            ->method('createSubscription')
            ->with(['subscription data'])
            ->willReturn($subscriptionMock);
        
        $this->assertSame(
            $subscriptionMock, $this->subscriptionService->createSubscription(['subscription data'], $websiteId)
        );
    }

    /**
     * @return array
     */
    public function createSubscriptionDataProvider()
    {
        return [
            'With website Id' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
            ],
            'Without website Id' => [
                'websiteId' => null,
                'expectedWebsiteId' => null
            ]
        ];
    }

    public function testLoadSubscription()
    {
        $subscriptionId = 111;
        $websiteId = 12;
        $subscriptionMock = $this->createSubscriptionMock();
        $this->initService($this->subscriptionPlatformService, $websiteId);

        $this->subscriptionPlatformService->expects($this->once())
            ->method('loadSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->assertSame(
            $subscriptionMock, $this->subscriptionService->loadSubscription($subscriptionId, $websiteId)
        );
    }

    public function testSaveSubscription()
    {
        $websiteId = 12;
        $subscriptionMock = $this->createSubscriptionMock();
        $this->initService($this->subscriptionPlatformService, $websiteId);

        $this->subscriptionPlatformService->expects($this->once())
            ->method('saveSubscription')
            ->with($subscriptionMock)
            ->willReturn($subscriptionMock);

        $this->assertSame(
            $subscriptionMock, $this->subscriptionService->saveSubscription($subscriptionMock, $websiteId)
        );
    }

    public function testLoadSubscriptions()
    {
        $websiteId = 12;
        $customerId = 3213;
        $subscriptionsMock = [$this->createSubscriptionMock(), $this->createSubscriptionMock()];
        $this->initService($this->subscriptionPlatformService, $websiteId);

        $this->subscriptionPlatformService->expects($this->once())
            ->method('loadSubscriptions')
            ->with($customerId)
            ->willReturn($subscriptionsMock);

        $this->assertEquals(
            $subscriptionsMock, 
            $this->subscriptionService->loadSubscriptionsByCustomer($customerId, $websiteId)
        );
    }

    public function testCancelSubscription()
    {
        $subscriptionId = 111;
        $websiteId = 12;
        $subscriptionMock = $this->createSubscriptionMock();
        $this->initService($this->subscriptionPlatformService, $websiteId);

        $this->subscriptionPlatformService->expects($this->once())
            ->method('cancelSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->subscriptionService->cancelSubscription($subscriptionId, $websiteId);
    }

    public function testPauseSubscription()
    {
        $subscriptionId = 111;
        $websiteId = 12;
        $subscriptionMock = $this->createSubscriptionMock();
        $this->initService($this->subscriptionPlatformService, $websiteId);

        $this->subscriptionPlatformService->expects($this->once())
            ->method('pauseSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->subscriptionService->pauseSubscription($subscriptionId, $websiteId);
    }

    public function testRestartSubscription()
    {
        $subscriptionId = 111;
        $websiteId = 12;
        $subscriptionMock = $this->createSubscriptionMock();
        $this->initService($this->subscriptionPlatformService, $websiteId);

        $this->subscriptionPlatformService->expects($this->once())
            ->method('restartSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->subscriptionService->restartSubscription($subscriptionId, $websiteId);
    }

    public function testSkipSubscription()
    {
        $subscriptionId = 111;
        $websiteId = 12;
        $subscriptionMock = $this->createSubscriptionMock();
        $this->initService($this->subscriptionPlatformService, $websiteId);

        $this->subscriptionPlatformService->expects($this->once())
            ->method('skipSubscription')
            ->with($subscriptionId)
            ->willReturn($subscriptionMock);

        $this->subscriptionService->skipSubscription($subscriptionId, $websiteId);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\SubscriptionInterface
     */
    private function createSubscriptionMock()
    {
        return $this->getMockBuilder(SubscriptionModel::class)->getMock();
    }
}
