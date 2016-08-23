<?php

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\Customer\CustomerInterface;

/**
 * @method \SubscribePro\Service\Customer\CustomerService getService($websiteId = null)
 */
class Customer extends AbstractService
{
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
