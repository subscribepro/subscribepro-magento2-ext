<?php

namespace Swarming\SubscribePro\Platform\Helper;

use SubscribePro\Service\Customer\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Customer
{
    /**
     * @var \SubscribePro\Service\Customer\CustomerService
     */
    protected $sdkCustomerService;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform
    ) {
        $this->sdkCustomerService = $platform->getSdk()->getCustomerService();
    }

    /**
     * @param int $magentoCustomerId
     * @return \SubscribePro\Service\Customer\CustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getCustomer($magentoCustomerId)
    {
        $subscribeProCustomers = $this->sdkCustomerService->loadCustomers(
            [CustomerInterface::MAGENTO_CUSTOMER_ID => $magentoCustomerId]
        );

        if (empty($subscribeProCustomers)) {
            throw new NoSuchEntityException(__('Platform customer is not found.'));
        }
        return $subscribeProCustomers[0];
    }
}
