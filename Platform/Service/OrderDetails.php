<?php

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\OrderDetails\OrderDetailsInterface;

/**
 * @method \SubscribePro\Service\OrderDetails\OrderDetailsService getService($websiteId = null)
 */
class OrderDetails extends AbstractService
{
    /**
     * @param array $orderDetails
     * @param int|null $websiteId
     * @return \SubscribePro\Service\OrderDetails\OrderDetailsInterface
     */
    public function createOrderDetails(array $orderDetailsData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createOrderDetails($orderDetailsData);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface $orderDetails
     * @param int|null $websiteId
     * @return \SubscribePro\Service\OrderDetails\OrderDetailsInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveOrderDetails(OrderDetailsInterface $orderDetails, $websiteId = null)
    {
        return $this->getService($websiteId)->saveNewOrderDetails($orderDetails);
    }
}
