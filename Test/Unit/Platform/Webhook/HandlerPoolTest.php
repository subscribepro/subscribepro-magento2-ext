<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Webhook;

use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;
use Swarming\SubscribePro\Platform\Webhook\HandlerPool;

class HandlerPoolTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $handlers
     * @param string $eventType
     * @expectedException \DomainException
     * @expectedExceptionMessageRegExp /Handler for '\w+' event is not found./
     * @dataProvider getHandlerIfHandlerNotFoundDataProvider
     */
    public function testGetHandlerIfHandlerNotFound($handlers, $eventType)
    {
        $handlerPool = new HandlerPool($handlers);
        $handlerPool->getHandler($eventType);
    }

    /**
     * @return array
     */
    public function getHandlerIfHandlerNotFoundDataProvider()
    {
        return [
            'Event type not set' => [
                'handlers' => ['some_type' => $this->createWebhookHandlerMock()],
                'eventType' => 'another_type'
            ],
            'Event not implement HandlerInterface' => [
                'handlers' => ['my_type' => 'string'],
                'eventType' => 'my_type'
            ]
        ];
    }

    public function testGetHandler()
    {
        $eventType = 'my_type';
        $handler = $this->createWebhookHandlerMock();

        $handlers = [
            'type' => $this->createWebhookHandlerMock(),
            $eventType => $handler
        ];

        $handlerPool = new HandlerPool($handlers);
        $this->assertSame($handler, $handlerPool->getHandler($eventType));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Webhook\HandlerInterface
     */
    private function createWebhookHandlerMock()
    {
        return $this->getMockBuilder(HandlerInterface::class)->getMock();
    }
}
