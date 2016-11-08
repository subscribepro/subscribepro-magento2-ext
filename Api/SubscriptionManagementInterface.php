<?php

namespace Swarming\SubscribePro\Api;

/**
 * Subscribe Pro subscription management interface.
 *
 * @api
 */
interface SubscriptionManagementInterface
{
    /**
     * @param int $customerId Customer ID.
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubscriptions($customerId);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param int $qty
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateQty($customerId, $subscriptionId, $qty);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param string $interval
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateInterval($customerId, $subscriptionId, $interval);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param string $nextOrderDate
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateNextOrderDate($customerId, $subscriptionId, $nextOrderDate);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param int $paymentProfileId
     * @param bool $isApplyToOther
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updatePaymentProfile($customerId, $subscriptionId, $paymentProfileId, $isApplyToOther = false);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateShippingAddress($customerId, $subscriptionId, $address);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @return string next order date
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function skip($customerId, $subscriptionId);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function pause($customerId, $subscriptionId);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function cancel($customerId, $subscriptionId);

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function restart($customerId, $subscriptionId);
}
