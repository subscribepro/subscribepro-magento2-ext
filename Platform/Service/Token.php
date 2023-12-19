<?php

namespace Swarming\SubscribePro\Platform\Service;

/**
 * @method \SubscribePro\Service\Subscription\SubscriptionService getService($websiteId = null)
 */
class Token extends AbstractService
{
    /**
     * @param array $tokenData
     * @param string|int|null $websiteId
     * @return mixed
     */
    public function createToken(array $tokenData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createToken($tokenData); /* @phpstan-ignore-line */
    }

    /**
     * @param \SubscribePro\Service\Token\TokenInterface $token
     * @param string|int|null $websiteId
     * @return mixed
     */
    public function saveToken($token, $websiteId = null)
    {
        return $this->getService($websiteId)->saveToken($token); /* @phpstan-ignore-line */
    }
}
