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
     * @param $websiteId
     * @return \SubscribePro\Service\Address\AddressInterface
     */
    public function createAddress(array $platformAddressData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createAddress($platformAddressData);
    }

    /**
     * @param $platformAddressId
     * @param $websiteId
     * @return \SubscribePro\Service\Address\AddressInterface
     */
    public function loadAddress($platformAddressId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadAddress($platformAddressId);
    }

    /**
     * @param AddressInterface $platformAddress
     * @param $websiteId
     * @return \SubscribePro\Service\Address\AddressInterface
     */
    public function saveAddress(AddressInterface $platformAddress, $websiteId = null)
    {
        return $this->getService($websiteId)->saveAddress($platformAddress);
    }

    /**
     * @param AddressInterface $platformAddress
     * @param $websiteId
     * @return \SubscribePro\Service\Address\AddressInterface
     */
    public function findOrSave(AddressInterface $platformAddress, $websiteId = null)
    {
        return $this->getService($websiteId)->findOrSave($platformAddress);
    }

    /**
     * @param $customerId
     * @param $websiteId
     * @return \SubscribePro\Service\Address\AddressInterface[]
     */
    public function loadAddresses($customerId = null, $websiteId = null)
    {
        return $this->getService($websiteId)->loadAddresses($customerId);
    }
}
