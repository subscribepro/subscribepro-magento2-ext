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
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
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
            'user_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'full_name' => implode(' ', [$customer->getFirstname(), $customer->getLastname()]),
        ];
    }
}
