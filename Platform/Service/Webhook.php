<?php

namespace Swarming\SubscribePro\Platform\Service;

/**
 * @method \SubscribePro\Service\Webhook\WebhookService getService($websiteId = null)
 */
class Webhook extends AbstractService
{
    /**
     * Read webhook event from request
     *
     * @param string|int|null $websiteId
     * @return \SubscribePro\Service\Webhook\EventInterface|bool
     */
    public function readEvent($websiteId = null)
    {
        return $this->getService($websiteId)->readEvent();
    }

    /**
     * @param int $eventId
     * @param string|int|null $websiteId
     * @return \SubscribePro\Service\Webhook\EventInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadEvent($eventId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadEvent($eventId);
    }
}
