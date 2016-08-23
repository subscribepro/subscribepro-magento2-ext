<?php

namespace Swarming\SubscribePro\Api;

interface PaymentTokenManagementInterface
{
    /**
     * @param int $customerId Customer ID.
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface[]
     */
    public function getSubscribeProTokensByCustomerId($customerId);
}
