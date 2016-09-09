<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Webhook;

use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;
use Swarming\SubscribePro\Platform\Webhook\HandlerPool;
use Swarming\SubscribePro\Platform\Webhook\Processor;
use SubscribePro\Service\Webhook\EventInterface as WebhookEventInterface;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Webhook\Processor
     */
    protected $webhookProcessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Webhook\HandlerPool
     */
    protected $webhookHandlerPoolMock;

    protected function setUp()
    {
        $this->webhookHandlerPoolMock = $this->getMockBuilder(HandlerPool::class)
            ->disableOriginalConstructor()->getMock();

        $this->webhookProcessor = new Processor($this->webhookHandlerPoolMock);
    }

    public function testProcessEventIfFailToGetHandler()
    {
        $exception = new \DomainException('error');
        $eventType = 'some_type';

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->once())->method('getType')->willReturn($eventType);

        $this->webhookHandlerPoolMock->expects($this->once())
            ->method('getHandler')
            ->with($eventType)
            ->willThrowException($exception);

        $this->webhookProcessor->processEvent($webhookEventMock);
    }

    public function testProcessEvent()
    {
        $eventType = 'some_type';

        $webhookEventMock = $this->createWebhookEventMock();
        $webhookEventMock->expects($this->once())->method('getType')->willReturn($eventType);

        $eventHandler = $this->createWebhookHandlerMock();
        $eventHandler->expects($this->once())->method('execute')->with($webhookEventMock);

        $this->webhookHandlerPoolMock->expects($this->once())
            ->method('getHandler')
            ->with($eventType)
            ->willReturn($eventHandler);

        $this->webhookProcessor->processEvent($webhookEventMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Webhook\HandlerInterface
     */
    private function createWebhookHandlerMock()
    {
        return $this->getMockBuilder(HandlerInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Webhook\EventInterface
     */
    private function createWebhookEventMock()
    {
        return $this->getMockBuilder(WebhookEventInterface::class)->getMock();
    }
}
