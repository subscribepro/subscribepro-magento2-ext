<?php

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\Subscription\SubscriptionService;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;

/**
 * @method SubscriptionService getService($websiteId = null)
 */
class Subscription extends AbstractService
{
    /**
     * @param array $subscriptionData
     * @param $websiteId
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface
     */
    public function createSubscription(array $subscriptionData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createSubscription($subscriptionData);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface $subscription
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveSubscription(SubscriptionInterface $subscription, $websiteId = null)
    {
        return $this->getService($websiteId)->saveSubscription($subscription);
    }

    /**
     * @param int $subscriptionId
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadSubscription($subscriptionId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadSubscription($subscriptionId);
    }

    /**
     * @param int $customerId
     * @param int|null $websiteId
     * @param int $count
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface[]
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadSubscriptionsByCustomer($customerId, $websiteId = null, $count = 25)
    {
        return $this->getService($websiteId)->loadSubscriptions($customerId, $count);
    }

    /**
     * @param int $subscriptionId
     * @param int|null $websiteId
     * @throws \SubscribePro\Exception\HttpException
     */
    public function cancelSubscription($subscriptionId, $websiteId = null)
    {
        $this->getService($websiteId)->cancelSubscription($subscriptionId);
    }

    /**
     * @param int $subscriptionId
     * @param int|null $websiteId
     * @throws \SubscribePro\Exception\HttpException
     */
    public function pauseSubscription($subscriptionId, $websiteId = null)
    {
        $this->getService($websiteId)->pauseSubscription($subscriptionId);
    }

    /**
     * @param int $subscriptionId
     * @param int|null $websiteId
     * @throws \SubscribePro\Exception\HttpException
     */
    public function restartSubscription($subscriptionId, $websiteId = null)
    {
        $this->getService($websiteId)->restartSubscription($subscriptionId);
    }

    /**
     * @param int $subscriptionId
     * @param int|null $websiteId
     * @throws \SubscribePro\Exception\HttpException
     */
    public function skipSubscription($subscriptionId, $websiteId = null)
    {
        $this->getService($websiteId)->skipSubscription($subscriptionId);
    }
}
