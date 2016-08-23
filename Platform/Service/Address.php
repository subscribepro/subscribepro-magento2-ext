<?php

namespace Swarming\SubscribePro\Platform\Service;

/**
 * @method \SubscribePro\Service\Address\AddressService getService($websiteCode = null)
 */
class Address extends AbstractService
{
    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @param \SubscribePro\Service\Customer\CustomerInterface $platformCustomer
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \SubscribePro\Exception\HttpException
     * @throws \SubscribePro\Exception\InvalidArgumentException
     */
    public function findOrSaveAddress($address, $platformCustomer)
    {
        $platformAddress = $this->getService()->createAddress();
        $platformAddress->setCity($address->getCity())
            ->setCompany($address->getCompany())
            ->setCountry($address->getCountryId())
            ->setRegion($address->getRegion()->getRegion())
            ->setPostcode($address->getPostcode())
            ->setPhone($address->getTelephone())
            ->setCustomerId($platformCustomer->getId())
            ->setFirstName($address->getFirstname())
            ->setLastName($address->getLastname())
            ->setMiddleName($address->getMiddlename());
        $streets = $address->getStreet() ? : [];
        if (isset($streets[0])) {
            $platformAddress->setStreet1($streets[0]);
        }
        if (isset($streets[1])) {
            $platformAddress->setStreet2($streets[1]);
        }
        
        $address = $this->getService()->findOrSave($platformAddress);
        return $address;
    }
}
