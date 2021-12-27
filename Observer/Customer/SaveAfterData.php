<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class SaveAfterData implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    private $generalConfig;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    private $platformCustomerManager;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Customer
     */
    private $platformCustomerService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Swarming\SubscribePro\Platform\Service\Customer $platformCustomerService
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Swarming\SubscribePro\Platform\Service\Customer $platformCustomerService,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->generalConfig = $generalConfig;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->platformCustomerService = $platformCustomerService;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getData('customer_data_object');

        if (!$this->generalConfig->isEnabled((int)$customer->getWebsiteId())) {
            return;
        }

        /** @var \Magento\Customer\Api\Data\CustomerInterface $origCustomer */
        $origCustomer = $observer->getData('orig_customer_data_object');
        if ($origCustomer
            && !$this->isCustomerChanged($customer, $origCustomer)
        ) {
            return;
        }

        try {
            $this->savePlatformCustomer($customer);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \Magento\Customer\Api\Data\CustomerInterface $origCustomer
     * @return bool
     */
    private function isCustomerChanged(CustomerInterface $customer, CustomerInterface $origCustomer): bool
    {
        return $origCustomer->getEmail() !== $customer->getEmail()
            || $origCustomer->getFirstname() !== $customer->getFirstname()
            || $origCustomer->getLastname() !== $customer->getLastname()
            || $origCustomer->getGroupId() !== $customer->getGroupId();
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return void
     */
    private function savePlatformCustomer(CustomerInterface $customer): void
    {
        $websiteId = $customer->getWebsiteId();

        $platformCustomer = $this->platformCustomerManager->getCustomerByMagentoCustomerId(
            (int)$customer->getId(),
            $this->isCreateNewCustomerIfNotExist($websiteId),
            $websiteId
        );

        if ($platformCustomer) {
            $platformCustomer->setFirstName($customer->getFirstname());
            $platformCustomer->setLastName($customer->getLastname());
            $platformCustomer->setEmail($customer->getEmail());
            $platformCustomer->setMagentoCustomerGroupId($customer->getGroupId());

            $this->platformCustomerService->saveCustomer($platformCustomer, (int)$websiteId);
        }
    }

    /**
     * @param int|string|null $websiteId
     * @return bool
     */
    private function isCreateNewCustomerIfNotExist($websiteId): bool
    {
        return $this->generalConfig->isApplePayEnabled($websiteId);
    }
}
