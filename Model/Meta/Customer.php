<?php

namespace Swarming\SubscribePro\Model\Meta;

class Customer implements \Swarming\SubscribePro\Model\MetaUserInterface
{
    public const TYPE = 'customer';

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    private $platformCustomerManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->logger = $logger;
    }

    /**
     * @return array|null
     */
    public function getMeta()
    {
        $customer = $this->customerSession->getCustomer();
        return $customer instanceof \Magento\Customer\Model\Customer && $customer->getId()
            ? $this->getCustomerMeta($customer)
            : null;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    private function getCustomerMeta(\Magento\Customer\Model\Customer $customer): array
    {
        return [
            'customer_id' => $this->getPlatformCustomerId($customer->getId(), $customer->getWebsiteId()),
            'email' => $customer->getEmail(),
            'full_name' => implode(' ', [$customer->getFirstname(), $customer->getLastname()]),
        ];
    }

    /**
     * @param int $customerId
     * @param int $websiteId
     * @return int|null
     */
    private function getPlatformCustomerId($customerId, $websiteId)
    {
        try {
            $platformCustomer = $this->platformCustomerManager->getCustomerById($customerId, false, $websiteId);
            $platformCustomerId = $platformCustomer->getId();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $platformCustomerId = null;
        }
        return $platformCustomerId;
    }
}
