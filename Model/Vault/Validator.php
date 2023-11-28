<?php

namespace Swarming\SubscribePro\Model\Vault;

use Magento\Customer\Block\Widget\Telephone;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Exception\LocalizedException;
use SubscribePro\Service\Address\AddressInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class Validator
{
    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var Data
     */
    protected $directoryData;

    /**
     * @var Telephone
     */
    private Telephone $telephoneWidget;

    /**
     * @param RegionFactory $regionFactory
     * @param Data $directoryData
     * @param Telephone $telephoneWidget
     */
    public function __construct(
        RegionFactory $regionFactory,
        Data $directoryData,
        Telephone $telephoneWidget
    ) {
        $this->regionFactory = $regionFactory;
        $this->directoryData = $directoryData;
        $this->telephoneWidget = $telephoneWidget;
    }

    /**
     * @param array $profileData
     * @return array
     * @throws LocalizedException
     */
    public function validate(array $profileData)
    {
        if (empty($profileData[PaymentProfileInterface::CREDITCARD_MONTH])
            || empty($profileData[PaymentProfileInterface::CREDITCARD_YEAR])
            || empty($profileData[PaymentProfileInterface::BILLING_ADDRESS])
            || !is_array($profileData[PaymentProfileInterface::BILLING_ADDRESS])
        ) {
            throw new LocalizedException(__('Not all fields are filled.'));
        }

        $updatedAddress = $this->updateRegion($profileData[PaymentProfileInterface::BILLING_ADDRESS]);
        if (!$this->validateBillingAddress($updatedAddress)) {
            throw new LocalizedException(__('Not all billing address fields are filled.'));
        }

        $profileData[PaymentProfileInterface::BILLING_ADDRESS] = $updatedAddress;

        return $profileData;
    }

    /**
     * @param array $addressData
     * @return bool
     */
    protected function validateBillingAddress(array $addressData)
    {
        $isValid = true;
        $isRegionRequired = $this->directoryData->isRegionRequired($addressData[AddressInterface::COUNTRY]);
        $isZipCodeOptional = $this->directoryData->isZipCodeOptional($addressData[AddressInterface::COUNTRY]);
        $isPhoneRequired = $this->telephoneWidget->isRequired();
        if (empty($addressData[AddressInterface::FIRST_NAME])
            || empty($addressData[AddressInterface::LAST_NAME])
            || empty($addressData[AddressInterface::STREET1])
            || empty($addressData[AddressInterface::CITY])
            || empty($addressData[AddressInterface::COUNTRY])
            || (empty($addressData[AddressInterface::REGION]) && $isRegionRequired)
            || (empty($addressData[AddressInterface::POSTCODE]) && !$isZipCodeOptional)
            || ($isPhoneRequired && empty($addressData[AddressInterface::PHONE]))
        ) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array $addressData
     * @return array
     */
    protected function updateRegion(array $addressData)
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
