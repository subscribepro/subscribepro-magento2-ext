<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Observer\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SpGeneralConfig $generalConfig,
        PlatformManagerCustomer $platformCustomerManager,
        PlatformServiceCustomer $platformCustomerService,
        LoggerInterface $logger
    ) {
        $this->generalConfig = $generalConfig;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->platformCustomerService = $platformCustomerService;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $origCustomerData = $observer->getData('orig_customer_data_object');
        $customerData = $observer->getData('customer_data_object');
        $customerId = (int) $customerData->getId();

        $websiteId = (int) $customerData->getWebsiteId();
        if (!$this->generalConfig->isEnabled($websiteId)
            || !$customerId
            || !$origCustomerData
        ) {
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

        $platformCustomer = $this->getPlatformCustomerByMagentoCustomerId($customerId, $websiteId);
        if ($platformCustomer) {
            $this->updatePlatformCustomer($customerData, $platformCustomer);
        }
    }

    /**
     * @param int $customerId
     * @param int $websiteId
     * @return PlatformCustomerInterface|null
     */
    protected function getPlatformCustomerByMagentoCustomerId(
        int $customerId,
        int $websiteId
    ): ?PlatformCustomerInterface {
        try {
            $platformCustomer = $this->platformCustomerManager->getCustomerByMagentoCustomerId(
                $customerId,
                true,
                $websiteId
            );
        } catch (NoSuchEntityException $e) {
            $platformCustomer = null;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $platformCustomer = null;
        }

        return $platformCustomer;
    }

    /**
     * @param CustomerInterface $customer
     * @param PlatformCustomerInterface $platformCustomer
     */
    protected function updatePlatformCustomer(
        CustomerInterface $customer,
        PlatformCustomerInterface $platformCustomer
    ): void {
        try {
            $this->importCustomerData($platformCustomer, $customer);
            $this->platformCustomerService->saveCustomer($platformCustomer, $customer->getWebsiteId());
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param PlatformCustomerInterface $platformCustomer
     * @param CustomerInterface $customer
     */
    protected function importCustomerData(
        PlatformCustomerInterface $platformCustomer,
        CustomerInterface $customer
    ): void {
        $platformCustomer->setFirstName($customer->getFirstname());
        $platformCustomer->setLastName($customer->getLastname());
        $platformCustomer->setEmail($customer->getEmail());
        $platformCustomer->setMagentoCustomerGroupId($customer->getGroupId());
    }
}
