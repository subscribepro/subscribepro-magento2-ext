<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Api;

interface GetOrderStatusInterface
{
    /**
     * @param int $orderId
     * @return \Swarming\SubscribePro\Api\Data\OrderPaymentStateInterface
     */
    public function execute($orderId);
}
