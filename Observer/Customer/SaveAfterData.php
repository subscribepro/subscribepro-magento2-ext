<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
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
        $customerData = $observer->getData('customer_data_object');
        // typecast is needed here because config implicitly treats a string as a website code, and int as an id
        $websiteId = (int) $customerData->getWebsiteId();
        if (!$this->generalConfig->isEnabled($websiteId)
        ) {
            return;
        }

        $origCustomerData = $observer->getData('orig_customer_data_object');
        // When a new customer is created, $origCustomerData is null, therefore nothing is saved to S-Pro
        if (!$origCustomerData) {
            return;
        }

        // If there are no required changes, just return.
        if ($origCustomerData->getEmail() === $customerData->getEmail()
            && $origCustomerData->getFirstname() === $customerData->getFirstname()
            && $origCustomerData->getLastname() === $customerData->getLastname()
            && $origCustomerData->getGroupId() === $customerData->getGroupId()
        ) {
            return;
        }

        $customerId = (int) $customerData->getId();
        try {
            $platformCustomer = $this->platformCustomerManager->getCustomerByMagentoCustomerId(
                $customerId,
                true,
                $websiteId
            );
            $this->savePlatformCustomer($customerData, $platformCustomer);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param \SubscribePro\Service\Customer\CustomerInterface $platformCustomer\
     * @return void
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    private function savePlatformCustomer(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        PlatformCustomerInterface $platformCustomer
    ): void {
        $platformCustomer->setFirstName($customer->getFirstname());
        $platformCustomer->setLastName($customer->getLastname());
        $platformCustomer->setEmail($customer->getEmail());
        $platformCustomer->setMagentoCustomerGroupId($customer->getGroupId());

        $this->platformCustomerService->saveCustomer($platformCustomer, $customer->getWebsiteId());
    }
}
