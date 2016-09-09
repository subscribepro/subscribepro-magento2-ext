<?php

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\Customer\CustomerInterface;

/**
 * @method \SubscribePro\Service\Customer\CustomerService getService($websiteId = null)
 */
class Customer extends AbstractService
{
    /**
     * @param array $platformCustomerData
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     */
    public function createCustomer(array $platformCustomerData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createCustomer($platformCustomerData);
    }

    /**
     * @param \SubscribePro\Service\Customer\CustomerInterface $platformCustomer
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveCustomer(CustomerInterface $platformCustomer, $websiteId = null)
    {
        return $this->getService($websiteId)->saveCustomer($platformCustomer);
    }

    /**
     * @param int $platformCustomerId
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadCustomer($platformCustomerId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadCustomer($platformCustomerId);
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
