<?php

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\Customer\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterface as MagentoCustomer;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @method \SubscribePro\Service\Customer\CustomerService getService($websiteId = null)
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
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getCustomer($magentoCustomerId, $createIfNotExist = false, $websiteId = null)
    {
        $platformProCustomers = $this->loadCustomers(
            [CustomerInterface::MAGENTO_CUSTOMER_ID => $magentoCustomerId],
            $websiteId
        );

        if (!empty($platformProCustomers)) {
            $platformCustomer = $platformProCustomers[0];
        } else if ($createIfNotExist) {
            $customer = $this->customerRepository->getById($magentoCustomerId);
            $platformCustomer = $this->createPlatformCustomer($customer, $websiteId);
        } else {
            throw new NoSuchEntityException(__('Platform customer is not found.'));
        }

        return $platformCustomer;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function createPlatformCustomer(MagentoCustomer $customer, $websiteId = null)
    {
        $platformCustomer = $this->createCustomer([], $websiteId);
        $platformCustomer->setMagentoCustomerId($customer->getId());
        $platformCustomer->setEmail($customer->getEmail());
        $platformCustomer->setFirstName($customer->getFirstname());
        $platformCustomer->setMiddleName($customer->getMiddlename());
        $platformCustomer->setLastName($customer->getLastname());
        $platformCustomer->setMagentoCustomerGroupId($customer->getGroupId());
        $platformCustomer->setMagentoWebsiteId($customer->getWebsiteId());

        $this->saveCustomer($platformCustomer, $websiteId);
        return $platformCustomer;
    }

    /**
     * @param array $customerData
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     */
    public function createCustomer(array $customerData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createCustomer($customerData);
    }

    /**
     * @param \SubscribePro\Service\Customer\CustomerInterface $customer
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveCustomer(CustomerInterface $customer, $websiteId = null)
    {
        return $this->getService($websiteId)->saveCustomer($customer);
    }

    /**
     * @param int $customerId
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadCustomer($customerId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadCustomer($customerId);
    }

    /**
     * Retrieve an array of all customers. Customers may be filtered.
     *  Available filters:
     * - magento_customer_id
     * - email
     * - first_name
     * - last_name
     *
     * @param array $filters
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface[]
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadCustomers(array $filters = [], $websiteId = null)
    {
        return $this->getService($websiteId)->loadCustomers($filters);
    }
}
