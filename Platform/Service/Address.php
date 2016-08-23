<?php

namespace Swarming\SubscribePro\Platform\Service;

use Swarming\SubscribePro\Api\Data\AddressInterface;

/**
 * @method \SubscribePro\Service\Address\AddressService getService($websiteId = null)
 */
class Address extends AbstractService
{
    /**
     * @param \Magento\Customer\Model\Address\AbstractAddress $address
     * @param int $platformCustomerId
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \SubscribePro\Exception\HttpException
     * @throws \SubscribePro\Exception\InvalidArgumentException
     */
    public function findOrSaveAddress($address, $platformCustomerId, $websiteId = null)
    {
        $platformAddress = $this->createAddress([], $websiteId);
        $platformAddress->setCity($address->getCity())
            ->setCompany($address->getCompany())
            ->setCountry($address->getCountryId())
            ->setRegion($address->getRegionCode())
            ->setStreet1($address->getStreetLine(1))
            ->setStreet2($address->getStreetLine(2))
            ->setPostcode($address->getPostcode())
            ->setPhone($address->getTelephone())
            ->setCustomerId($platformCustomerId)
            ->setFirstName($address->getFirstname())
            ->setLastName($address->getLastname())
            ->setMiddleName($address->getMiddlename());

        $this->findOrSave($platformAddress, $websiteId);
        return $platformAddress;
    }

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
