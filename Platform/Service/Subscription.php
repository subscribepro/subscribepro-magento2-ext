<?php

namespace Swarming\SubscribePro\Platform\Service;

/**
 * @method \SubscribePro\Service\Subscription\SubscriptionService getService($websiteCode = null)
 */
class Subscription extends AbstractService
{
    /**
     * @param int $customerId
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface[]
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadSubscriptionsByCustomer($customerId)
    {
        return $this->getService()->loadSubscriptions($customerId);
    }

    /**
     * @param int $subscriptionId
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadSubscription($subscriptionId)
    {
        return $this->getService()->loadSubscription($subscriptionId);
    }

    /**
     * @param \SubscribePro\Service\Subscription\SubscriptionInterface $subscription
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveSubscription($subscription)
    {
        return $this->getService()->saveSubscription($subscription);
    }

    /**
     * @param int $subscriptionId
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function skip($subscriptionId)
    {
        $this->getService()->skipSubscription($subscriptionId);
    }

    /**
     * @param int $subscriptionId
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function cancel($subscriptionId)
    {
        $this->getService()->cancelSubscription($subscriptionId);
    }

    /**
     * @param int $subscriptionId
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function pause($subscriptionId)
    {
        $this->getService()->pauseSubscription($subscriptionId);
    }
    
    /**
     * @param int $subscriptionId
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function restart($subscriptionId)
    {
        $this->getService()->restartSubscription($subscriptionId);
    }
}
