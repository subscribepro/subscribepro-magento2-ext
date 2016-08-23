<?php

namespace Swarming\SubscribePro\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data;
use Swarming\SubscribePro\Api\AddressManagementInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;

class AddressManagement implements AddressManagementInterface
{
    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     */
    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig
    ) {
        $this->addressRepository = $addressRepository;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
    }

    /**
     * @param int $customerId
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveInAddressBook($customerId, $address)
    {
        try {
            $customerAddress = $address->exportCustomerAddress();
            $customerAddress->setCustomerId($customerId);
            $this->addressRepository->save($customerAddress);
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('An error occurred while saving address in the address book.'));
        }

        return $this->getAddressInline($customerAddress);
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    protected function getAddressInline($address)
    {
        return $this->addressConfig
            ->getFormatByCode(AddressConfig::DEFAULT_ADDRESS_FORMAT)
            ->getData('renderer')
            ->renderArray($this->addressMapper->toFlatArray($address));
    }
}
