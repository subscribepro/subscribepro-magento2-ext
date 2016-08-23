<?php

namespace Swarming\SubscribePro\Platform\Service;

use Swarming\SubscribePro\Api\Data\AddressInterface;

/**
 * @method \SubscribePro\Service\Address\AddressService getService($websiteId = null)
 */
class Address extends AbstractService
{
    /**
     * @param array $addressData
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     */
    public function createAddress(array $addressData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createAddress($addressData);
    }

    /**
     * @param int $addressId
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadAddress($addressId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadAddress($addressId);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\AddressInterface $address
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveAddress(AddressInterface $address, $websiteId = null)
    {
        return $this->getService($websiteId)->saveAddress($address);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\AddressInterface $address
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function findOrSave(AddressInterface $address, $websiteId = null)
    {
        return $this->getService($websiteId)->findOrSave($address);
    }

    /**
     * @param int|null $customerId
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface[]
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadAddresses($customerId = null, $websiteId = null)
    {
        return $this->getService($websiteId)->loadAddresses($customerId);
    }
}
