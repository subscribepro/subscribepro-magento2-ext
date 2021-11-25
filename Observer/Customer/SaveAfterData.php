<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Swarming\SubscribePro\Model\Config\General as SpGeneralConfig;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformManagerCustomer;
use Swarming\SubscribePro\Platform\Service\Customer as PlatformServiceCustomer;

class SaveAfterData implements ObserverInterface
{
    /**
     * @var SpGeneralConfig
     */
    private $generalConfig;

    /**
     * @var PlatformManagerCustomer
     */
    private $platformCustomerManager;

    /**
     * @var PlatformServiceCustomer
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
        SpGeneralConfig $generalConfig,
        PlatformManagerCustomer $platformCustomerManager,
        PlatformServiceCustomer $platformCustomerService,
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
        // typecast is needed here because config implicitly treats a string as a website code, and int as an id
        $websiteId = (int)$customer->getWebsiteId();
        if (!$this->generalConfig->isEnabled($websiteId)
        ) {
            return;
        }
        /** @var \Magento\Customer\Api\Data\CustomerInterface $origCustomer */
        $origCustomer = $observer->getData('orig_customer_data_object');
        // When a new customer is created, $origCustomer is null
        // If there are no required changes, return.
        if ($origCustomer
            && $origCustomer->getEmail() === $customer->getEmail()
            && $origCustomer->getFirstname() === $customer->getFirstname()
            && $origCustomer->getLastname() === $customer->getLastname()
            && $origCustomer->getGroupId() === $customer->getGroupId()
        ) {
            return;
        }

        $this->savePlatformCustomer($customer);
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return void
     */
    private function savePlatformCustomer($customer): void
    {
        $websiteId = $customer->getWebsiteId();
        // Apple Pay will not work for new customers if they are not available on the S-Pro end,
        // but it is not necessary to keep new customers for the other gateways
        $createIfNotExist = $this->generalConfig->isApplePayEnabled($websiteId);
        $customerId = (int)$customer->getId();
        try {
            $platformCustomer = $this->platformCustomerManager->getCustomerByMagentoCustomerId(
                $customerId,
                $createIfNotExist,
                $websiteId
            );
            if (!$platformCustomer) {
                return;
            }

            $platformCustomer->setFirstName($customer->getFirstname());
            $platformCustomer->setLastName($customer->getLastname());
            $platformCustomer->setEmail($customer->getEmail());
            $platformCustomer->setMagentoCustomerGroupId($customer->getGroupId());

            $this->platformCustomerService->saveCustomer($platformCustomer, $websiteId);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
