<?php

namespace Swarming\SubscribePro\Plugin\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CustomerRepository
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManager;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Customer
     */
    protected $platformCustomerService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Swarming\SubscribePro\Platform\Service\Customer $platformCustomerService
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Swarming\SubscribePro\Platform\Service\Customer $platformCustomerService,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->platformCustomerManager = $platformCustomerManager;
        $this->platformCustomerService = $platformCustomerService;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $subject
     * @param \Closure $proceed
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $passwordHash
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function aroundSave(CustomerRepositoryInterface $subject, \Closure $proceed, CustomerInterface $customer, $passwordHash = null)
    {
        $platformCustomer = $this->getPlatformCustomer($customer);

        $customer = $proceed($customer, $passwordHash);

        if ($platformCustomer) {
            $this->updatePlatformCustomer($customer, $platformCustomer);
        }
        return $customer;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \SubscribePro\Service\Customer\CustomerInterface|null
     */
    protected function getPlatformCustomer($customer)
    {
        try {
            $platformCustomer = $this->platformCustomerManager->getCustomerById($customer->getId(), false, $customer->getWebsiteId());
        } catch (NoSuchEntityException $e) {
            $platformCustomer = null;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $platformCustomer = null;
        }

        return $platformCustomer;
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \SubscribePro\Service\Customer\CustomerInterface $platformCustomer
     */
    protected function updatePlatformCustomer($customer, $platformCustomer)
    {
        try {
            $this->importCustomerData($platformCustomer, $customer);
            $this->platformCustomerService->saveCustomer($platformCustomer, $customer->getWebsiteId());
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param \SubscribePro\Service\Customer\CustomerInterface $platformCustomer
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     */
    protected function importCustomerData(PlatformCustomerInterface $platformCustomer, CustomerInterface $customer)
    {
        $platformCustomer->setFirstName($customer->getFirstname());
        $platformCustomer->setLastName($customer->getLastname());
        $platformCustomer->setEmail($customer->getEmail());
    }
}
