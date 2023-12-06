<?php

namespace Swarming\SubscribePro\Platform\Manager;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;

class Customer
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Customer
     */
    private $platformCustomerService;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\Customer $platformCustomerService
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\Customer $platformCustomerService,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->platformCustomerService = $platformCustomerService;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param int $customerId
     * @param bool $createIfNotExist
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getCustomerById($customerId, $createIfNotExist = false, $websiteId = null)
    {
        $customer = $this->customerRepository->getById($customerId);
        // If $websiteId is null, use the customer's websiteId as a fallback
        $websiteId = $websiteId !== null ? $websiteId : $customer->getWebsiteId();
        return $this->getCustomer($customer->getEmail(), $createIfNotExist, $websiteId);
    }

    /**
     * @param string   $customerEmail
     * @param bool     $createIfNotExist
     * @param int|null $websiteId
     *
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getCustomer($customerEmail, $createIfNotExist = false, $websiteId = null)
    {
        $platformCustomers = $this->platformCustomerService->loadCustomers(
            [PlatformCustomerInterface::EMAIL => $customerEmail],
            $websiteId
        );

        if (!empty($platformCustomers)) {
            $platformCustomer = $platformCustomers[0];
        } elseif ($createIfNotExist) {
            $customer = $this->customerRepository->get($customerEmail, $websiteId);
            $platformCustomer = $this->createPlatformCustomer($customer, $websiteId);
        } else {
            throw new NoSuchEntityException(__('Platform customer is not found.'));
        }

        return $platformCustomer;
    }

    /**
     * @param $customerEmail
     * @param $createIfNotExist
     * @param $websiteId
     * @return PlatformCustomerInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCustomerByEmail($customerEmail, $createIfNotExist = false, $websiteId = null)
    {
        $platformCustomer = [];
        $platformCustomers = $this->platformCustomerService->loadCustomers(
            [PlatformCustomerInterface::EMAIL => $customerEmail],
            $websiteId
        );

        if (!empty($platformCustomers)) {
            $platformCustomer = $platformCustomers[0];
        } elseif ($createIfNotExist) {
            $customer = $this->customerRepository->get($customerEmail, $websiteId);
            $platformCustomer = $this->createPlatformCustomer($customer, $websiteId);
        }

        return $platformCustomer;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    private function createPlatformCustomer(
        CustomerInterface $customer,
        ?int $websiteId = null
    ): PlatformCustomerInterface {
        $platformCustomer = $this->platformCustomerService->createCustomer([], $websiteId);
        $platformCustomer->setMagentoCustomerId($customer->getId());
        $platformCustomer->setEmail($customer->getEmail());
        $platformCustomer->setFirstName($customer->getFirstname());
        $platformCustomer->setMiddleName($customer->getMiddlename());
        $platformCustomer->setLastName($customer->getLastname());
        $platformCustomer->setMagentoCustomerGroupId($customer->getGroupId());
        $platformCustomer->setMagentoWebsiteId($customer->getWebsiteId());

        $this->platformCustomerService->saveCustomer($platformCustomer, $websiteId);
        return $platformCustomer;
    }
}
