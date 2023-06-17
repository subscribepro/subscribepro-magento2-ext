<?php

namespace Swarming\SubscribePro\Api;

interface CartManagementInterface
{
    /**
     * @param int $customerId
     * @return int Quote ID
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createEmptyCartForCustomer($customerId);

    /**
     * @param int $customerId
     * @param int $storeId
     * @return \Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createCustomerCart($customerId, $storeId);

    /**
     * @param int $cartId
     * @return string|null
     */
    public function deactivateCustomerCart($cartId);
}
