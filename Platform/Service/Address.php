<?php

namespace Swarming\SubscribePro\Platform\Service;

use Swarming\SubscribePro\Api\Data\AddressInterface;

/**
 * @method \SubscribePro\Service\Address\AddressService getService($websiteId = null)
 */
class Address extends AbstractService
{
    /**
     * @param array $platformAddressData
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     */
    public function createAddress(array $platformAddressData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createAddress($platformAddressData);
    }

    /**
     * @param int $platformAddressId
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadAddress($platformAddressId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadAddress($platformAddressId);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\AddressInterface $platformAddress
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveAddress(AddressInterface $platformAddress, $websiteId = null)
    {
        return $this->getService($websiteId)->saveAddress($platformAddress);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\AddressInterface $platformAddress
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function findOrSave(AddressInterface $platformAddress, $websiteId = null)
    {
        return $this->getService($websiteId)->findOrSave($platformAddress);
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
