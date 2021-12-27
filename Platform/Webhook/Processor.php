<?php

namespace Swarming\SubscribePro\Platform\Webhook;

class Processor
{
    /**
     * @var \Swarming\SubscribePro\Platform\Webhook\HandlerPool
     */
    protected $handlerPool;

    /**
     * @param \Swarming\SubscribePro\Platform\Webhook\HandlerPool $handlerPool
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Webhook\HandlerPool $handlerPool
    ) {
        $this->handlerPool = $handlerPool;
    }

    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     */
    public function processEvent($event)
    {
        try {
            $handler = $this->handlerPool->getHandler($event->getType());
            $handler->execute($event);
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (\DomainException $e) {
            /* Do nothing */
        }
    }
}
