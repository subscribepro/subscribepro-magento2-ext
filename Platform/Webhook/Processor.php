<?php

namespace Swarming\SubscribePro\Platform\Webhook;

use Psr\Log\LoggerInterface;

class Processor
{
    /**
     * @var \Swarming\SubscribePro\Platform\Webhook\HandlerPool
     */
    protected $handlerPool;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param HandlerPool     $handlerPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Webhook\HandlerPool $handlerPool,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->handlerPool = $handlerPool;
        $this->logger = $logger;
    }

    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     */
    public function processEvent($event)
    {
        try {
            $handler = $this->handlerPool->getHandler($event->getType());
            $handler->execute($event);
        } catch (\DomainException $e) {
            $this->logger->debug($e->getMessage());
            $this->logger->warning('Error while process webhook!');
        }
    }
}
