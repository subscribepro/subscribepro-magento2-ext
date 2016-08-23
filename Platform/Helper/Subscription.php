<?php

namespace Swarming\SubscribePro\Platform\Helper;

class Subscription
{
    /**
     * @var \SubscribePro\Service\Subscription\SubscriptionService
     */
    protected $sdkSubscriptionService;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform
    ) {
        $this->sdkSubscriptionService = $platform->getSdk()->getSubscriptionService();
    }

    /**
     * @param int $customerId
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface[]
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadSubscriptionsByCustomer($customerId)
    {
        return $this->sdkSubscriptionService->loadSubscriptions($customerId);
    }

    /**
     * @param int $subscriptionId
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadSubscription($subscriptionId)
    {
        return $this->sdkSubscriptionService->loadSubscription($subscriptionId);
    }

    /**
     * @param \SubscribePro\Service\Subscription\SubscriptionInterface $subscription
     * @return \SubscribePro\Service\Subscription\SubscriptionInterface
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveSubscription($subscription)
    {
        return $this->sdkSubscriptionService->saveSubscription($subscription);
    }

    /**
     * @param int $subscriptionId
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function skip($subscriptionId)
    {
        $this->sdkSubscriptionService->skipSubscription($subscriptionId);
    }

    /**
     * @param int $subscriptionId
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function cancel($subscriptionId)
    {
        $this->sdkSubscriptionService->cancelSubscription($subscriptionId);
    }

    /**
     * @param int $subscriptionId
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function pause($subscriptionId)
    {
        $this->sdkSubscriptionService->pauseSubscription($subscriptionId);
    }
    
    /**
     * @param int $subscriptionId
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function restart($subscriptionId)
    {
        $this->sdkSubscriptionService->restartSubscription($subscriptionId);
    }
}
