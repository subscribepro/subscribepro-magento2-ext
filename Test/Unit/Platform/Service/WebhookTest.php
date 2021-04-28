<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Service;

use SubscribePro\Service\Webhook\WebhookService;
use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Platform\Service\Webhook;

class WebhookTest extends AbstractService
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Webhook
     */
    protected $webhookService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Webhook\WebhookService
     */
    protected $webhookPlatformService;

    protected function setUp(): void
    {
        $this->platformMock = $this->createPlatformMock();
        $this->webhookPlatformService = $this->getMockBuilder(WebhookService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->webhookService = new Webhook($this->platformMock, $this->name);
    }

    public function testReadEvent()
    {
        $websiteId = 12;
        $webhookEventMock = $this->createWebhookEventMock();
        $this->initService($this->webhookPlatformService, $websiteId);

        $this->webhookPlatformService->expects($this->once())
            ->method('readEvent')
            ->willReturn($webhookEventMock);

        $this->assertSame($webhookEventMock, $this->webhookService->readEvent($websiteId));
    }

    public function testLoadEvent()
    {
        $eventId = 111;
        $websiteId = 12;
        $webhookEventMock = $this->createWebhookEventMock();
        $this->initService($this->webhookPlatformService, $websiteId);

        $this->webhookPlatformService->expects($this->once())
            ->method('loadEvent')
            ->with($eventId)
            ->willReturn($webhookEventMock);

        $this->assertSame(
            $webhookEventMock,
            $this->webhookService->loadEvent($eventId, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Webhook\EventInterface
     */
    private function createWebhookEventMock()
    {
        return $this->getMockBuilder(EventInterface::class)->getMock();
    }
}
