<?php

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\Customer\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomer;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @method \SubscribePro\Service\Customer\CustomerService getService($websiteCode = null)
 */
class Customer extends AbstractService
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param string $name
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform,
        $name,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
        parent::__construct($platform, $name);
    }

    /**
     * @param int $magentoCustomerId
     * @param bool $createIfNotExist
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getCustomer($magentoCustomerId, $createIfNotExist = false)
    {
        $subscribeProCustomers = $this->getService()->loadCustomers(
            [CustomerInterface::MAGENTO_CUSTOMER_ID => $magentoCustomerId]
        );

        if (!empty($subscribeProCustomers)) {
            $platformCustomer = $subscribeProCustomers[0];
        } else if ($createIfNotExist) {
            $customer = $this->customerRepository->getById($magentoCustomerId);
            $platformCustomer = $this->createPlatformCustomer($customer);
        } else {
            throw new NoSuchEntityException(__('Platform customer is not found.'));
        }

        return $platformCustomer;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function createPlatformCustomer(MagentoCustomer $customer)
    {
        $platformCustomer = $this->getService()->createCustomer();
        $platformCustomer->setMagentoCustomerId($customer->getId());
        $platformCustomer->setEmail($customer->getEmail());
        $platformCustomer->setFirstName($customer->getFirstname());
        $platformCustomer->setMiddleName($customer->getMiddlename());
        $platformCustomer->setLastName($customer->getLastname());
        $platformCustomer->setMagentoCustomerGroupId($customer->getGroupId());
        $platformCustomer->setMagentoWebsiteId($customer->getWebsiteId());

        $this->getService()->saveCustomer($platformCustomer);
        return $platformCustomer;
    }
}
