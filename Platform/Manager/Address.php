<?php

namespace Swarming\SubscribePro\Platform\Manager;

class Address
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Address
     */
    protected $platformAddressService;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\Address $platformAddressService
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\Address $platformAddressService
    ) {
        $this->platformAddressService = $platformAddressService;
    }

    /**
     * @param \Magento\Customer\Model\Address\AbstractAddress $address
     * @param int $platformCustomerId
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Address\AddressInterface
     * @throws \SubscribePro\Exception\HttpException
     * @throws \SubscribePro\Exception\InvalidArgumentException
     */
    public function findOrSaveAddress($address, $platformCustomerId, $websiteId = null)
    {
        $platformAddress = $this->platformAddressService->createAddress([], $websiteId);
        $platformAddress->setCity($address->getCity())
            ->setCompany($address->getCompany())
            ->setCountry($address->getCountryId())
            ->setRegion($address->getRegionCode())
            ->setStreet1($address->getStreetLine(1))
            ->setStreet2($address->getStreetLine(2))
            ->setStreet3($address->getStreetLine(3))
            ->setPostcode($address->getPostcode())
            ->setPhone($address->getTelephone())
            ->setCustomerId($platformCustomerId)
            ->setFirstName($address->getFirstname())
            ->setLastName($address->getLastname())
            ->setMiddleName($address->getMiddlename());

        $this->platformAddressService->findOrSave($platformAddress, $websiteId);
        return $platformAddress;
    }
}
