<?php

namespace Swarming\SubscribePro\Platform\Webhook;

use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;

class HandlerPool
{
    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * @param array $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * @param string $eventType
     * @return \Swarming\SubscribePro\Platform\Webhook\HandlerInterface
     * @throws \DomainException
     */
    public function getHandler($eventType)
    {
        if (!$this->hasHandler($eventType)) {
            throw new \DomainException("Handler for '{$eventType}' event is not found.");
        }
        return $this->handlers[$eventType];
    }

    /**
     * @param string $eventType
     * @return bool
     */
    protected function hasHandler($eventType)
    {
        return isset($this->handlers[$eventType]) && $this->handlers[$eventType] instanceof HandlerInterface;
    }
}
