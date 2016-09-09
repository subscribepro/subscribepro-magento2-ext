<?php

namespace Swarming\SubscribePro\Platform\Webhook;

use SubscribePro\Service\Webhook\EventInterface;

interface HandlerInterface
{
    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     */
    public function execute(EventInterface $event);
}
