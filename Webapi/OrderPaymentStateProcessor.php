<?php

namespace Swarming\SubscribePro\Webapi;

use Swarming\SubscribePro\Api\Data\OrderPaymentStateInterface;

class OrderPaymentStateProcessor
{
    /**
     * @param \Swarming\SubscribePro\Api\Data\OrderPaymentStateInterface $dataObject
     * @param array $result
     * @return array
     */
    public function execute(OrderPaymentStateInterface $dataObject, array $result): array
    {
        $result[OrderPaymentStateInterface::GATEWAY_SPECIFIC_FIELDS] = $dataObject->getGatewaySpecificFields();
        return $result;
    }
}
