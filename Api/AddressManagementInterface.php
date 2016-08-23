<?php

namespace Swarming\SubscribePro\Api;

interface AddressManagementInterface
{
    /**
     * @param int $customerId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveInAddressBook($customerId, $address);
}
