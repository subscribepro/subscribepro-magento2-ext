<?php

namespace Swarming\SubscribePro\Model\Vault;

use SubscribePro\Service\Address\AddressInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class Validator
{
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $directoryData;

    /**
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     */
    public function __construct(
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Helper\Data $directoryData
    ) {
        $this->regionFactory = $regionFactory;
        $this->directoryData = $directoryData;
    }

    /**
     * @param array $profileData
     * @return bool
     */
    public function validate(array $profileData)
    {
        $isValid = true;
        if (empty($profileData[PaymentProfileInterface::CREDITCARD_MONTH])
            || empty($profileData[PaymentProfileInterface::CREDITCARD_YEAR])
            || empty($profileData[PaymentProfileInterface::BILLING_ADDRESS])
            || !is_array($profileData[PaymentProfileInterface::BILLING_ADDRESS])
            || !$this->validateBillingAddress($profileData[PaymentProfileInterface::BILLING_ADDRESS])
        ) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array $addressData
     * @return bool
     */
    protected function validateBillingAddress(array $addressData)
    {
        $this->updateRegion($addressData);

        $isValid = true;
        if (empty($addressData[AddressInterface::FIRST_NAME])
            || empty($addressData[AddressInterface::LAST_NAME])
            || empty($addressData[AddressInterface::STREET1])
            || empty($addressData[AddressInterface::CITY])
            || empty($addressData[AddressInterface::COUNTRY])
            || (empty($addressData[AddressInterface::REGION]) && $this->directoryData->isRegionRequired($addressData[AddressInterface::COUNTRY]))
            || (empty($addressData[AddressInterface::POSTCODE]) && !$this->directoryData->isZipCodeOptional($addressData[AddressInterface::COUNTRY]))
            || empty($addressData[AddressInterface::PHONE])
        ) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array $addressData
     * @return array
     */
    protected function updateRegion(array &$addressData)
    {
        if (empty($addressData['region_id']) || empty($addressData['country'])) {
            return $addressData;
        }

        $region = $this->regionFactory->create();
        $region->load($addressData['region_id']);
        if ($region->getCode() && $region->getCountryId() == $addressData['country']) {
            $addressData['region'] = $region->getCode();
        }
        return $addressData;
    }
}
