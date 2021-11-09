<?php

namespace Swarming\SubscribePro\Service;

use Magento\Framework\App\Area;
use SubscribePro\Exception\HttpException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Swarming\SubscribePro\Api\SubscriptionManagementInterface;

class SubscriptionManagement implements SubscriptionManagementInterface
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManager;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformSubscriptionService;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Address
     */
    protected $platformAddressManager;

    /**
     * @var \Swarming\SubscribePro\Helper\SubscriptionProduct
     */
    protected $subscriptionProductHelper;

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionOptions
     */
    protected $subscriptionOptionConfig;

    /**
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $design;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService
     * @param \Swarming\SubscribePro\Platform\Manager\Address $platformAddressManager
     * @param \Swarming\SubscribePro\Helper\SubscriptionProduct $subscriptionProductHelper
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionConfig
     * @param \SubscribePro\Utils\SubscriptionUtils $subscriptionUtils
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService,
        \Swarming\SubscribePro\Platform\Manager\Address $platformAddressManager,
        \Swarming\SubscribePro\Helper\SubscriptionProduct $subscriptionProductHelper,
        \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionConfig,
        \SubscribePro\Utils\SubscriptionUtils $subscriptionUtils,
        \Magento\Framework\View\DesignInterface $design,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->platformProductManager = $platformProductManager;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->platformSubscriptionService = $platformSubscriptionService;
        $this->platformAddressManager = $platformAddressManager;
        $this->subscriptionProductHelper = $subscriptionProductHelper;
        $this->subscriptionOptionConfig = $subscriptionOptionConfig;
        $this->subscriptionUtils = $subscriptionUtils;
        $this->design = $design;
        $this->logger = $logger;
    }

    protected function enableFrontendDesignArea()
    {
        $this->design->setDesignTheme(
            $this->design->getConfigurationDesignTheme(Area::AREA_FRONTEND),
            Area::AREA_FRONTEND
        );
    }

    /**
     * @param int $customerId
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubscriptions($customerId)
    {
        $this->enableFrontendDesignArea();

        try {
            $count = $this->subscriptionOptionConfig->getMySubscriptionsLoadCount() ?: 25;
            $subscriptions = $this->getSubscriptionByCustomerId($customerId, $count);
            if ($subscriptions) {
                $this->subscriptionProductHelper->linkProducts($subscriptions);
            }
        } catch (NoSuchEntityException $e) {
            $subscriptions = [];
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('Unable to load subscriptions.'));
        }

        return $subscriptions;
    }

    /**
     * @param int $customerId
     * @param int $count
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionInterface[]
     */
    protected function getSubscriptionByCustomerId($customerId, $count = 25)
    {
        $platformCustomer = $this->platformCustomerManager->getCustomerById($customerId);
        $subscriptions = $this->platformSubscriptionService->loadSubscriptionsByCustomer(
            $platformCustomer->getId(),
            null,
            $count
        );
        $subscriptions = $this->subscriptionUtils->filterAndSortSubscriptionListForDisplay($subscriptions);

        return $subscriptions;
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param int $qty
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateQty($customerId, $subscriptionId, $qty)
    {
        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $platformProduct = $this->platformProductManager->getProduct($subscription->getProductSku());
            if (($platformProduct->getMinQty() && $platformProduct->getMinQty() > $qty)
                || ($platformProduct->getMaxQty() && $platformProduct->getMaxQty() < $qty)
            ) {
                throw new LocalizedException(__(
                    'Invalid quantity, it must be in range from %1 to %2.',
                    $platformProduct->getMinQty(),
                    $platformProduct->getMaxQty()
                ));
            }

            $subscription->setQty($qty);
            $subscription->setSendCustomerNotificationEmail(true);

            $this->platformSubscriptionService->saveSubscription($subscription);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while updating quantity.'));
        }
        return true;
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param string $interval
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateInterval($customerId, $subscriptionId, $interval)
    {
        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $subscription->setInterval($interval);
            $subscription->setSendCustomerNotificationEmail(true);
            $this->platformSubscriptionService->saveSubscription($subscription);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while updating interval.'));
        }
        return true;
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param string $nextOrderDate
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateNextOrderDate($customerId, $subscriptionId, $nextOrderDate)
    {
        if ($nextOrderDate < $this->subscriptionOptionConfig->getEarliestDateForNextOrder()) {
            throw new LocalizedException(
                __('Invalid next order date, it must be not earlier than 2 days in the future.')
            );
        }

        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $subscription->setNextOrderDate($nextOrderDate);
            $subscription->setSendCustomerNotificationEmail(true);
            $this->platformSubscriptionService->saveSubscription($subscription);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while updating next order date.'));
        }
        return true;
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param int $paymentProfileId
     * @param bool $isApplyToOther
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updatePaymentProfile($customerId, $subscriptionId, $paymentProfileId, $isApplyToOther = false)
    {
        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $currentPaymentId = $subscription->getPaymentProfile()->getId();
            $this->setPaymentProfileProfile($subscription, $paymentProfileId);

            if ($isApplyToOther) {
                $subscriptions = $this->getSubscriptionByCustomerId($customerId);
                foreach ($subscriptions as $item) {
                    if ($item->getPaymentProfileId() == $currentPaymentId) {
                        $this->setPaymentProfileProfile($item, $paymentProfileId);
                    }
                }
            }
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while updating payment profile.'));
        }

        return $subscription->getPaymentProfile();
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface $subscription
     * @param $paymentProfileId
     */
    protected function setPaymentProfileProfile($subscription, $paymentProfileId)
    {
        $subscription->setPaymentProfileId($paymentProfileId);
        $subscription->setSendCustomerNotificationEmail(true);
        $this->platformSubscriptionService->saveSubscription($subscription);
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return \Swarming\SubscribePro\Api\Data\AddressInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateShippingAddress($customerId, $subscriptionId, $address)
    {
        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $platformCustomer = $this->platformCustomerManager->getCustomerById($customerId);
            $this->checkSubscriptionOwner($subscription, $customerId, $platformCustomer);

            $platformAddress = $this->platformAddressManager->findOrSaveAddress($address, $platformCustomer->getId());
            $subscription->setShippingAddressId($platformAddress->getId());
            $subscription->setSendCustomerNotificationEmail(true);
            $this->platformSubscriptionService->saveSubscription($subscription);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while updating subscription shipping address.'));
        }

        return $platformAddress;
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @return string next order date
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function skip($customerId, $subscriptionId)
    {
        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $this->platformSubscriptionService->skipSubscription($subscriptionId);
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while skipping next delivery.'));
        }
        return $subscription->getNextOrderDate('Y-m-d');
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function cancel($customerId, $subscriptionId)
    {
        if (!$this->subscriptionOptionConfig->isAllowedCancel()) {
            throw new LocalizedException(__('The subscription cancellation is not allowed.'));
        }

        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $this->platformSubscriptionService->cancelSubscription($subscriptionId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while canceling subscription.'));
        }
        return true;
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function pause($customerId, $subscriptionId)
    {
        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $this->platformSubscriptionService->pauseSubscription($subscriptionId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while pausing subscription.'));
        }
        return true;
    }

    /**
     * @param int $customerId
     * @param int $subscriptionId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function restart($customerId, $subscriptionId)
    {
        try {
            $subscription = $this->platformSubscriptionService->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $this->platformSubscriptionService->restartSubscription($subscriptionId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while restarting subscription.'));
        }
        return true;
    }

    /**
     * @param \SubscribePro\Service\Subscription\SubscriptionInterface $subscription
     * @param $customerId
     * @param null|\SubscribePro\Service\Customer\CustomerInterface
     * @throws \Magento\Framework\Exception\AuthorizationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function checkSubscriptionOwner($subscription, $customerId, $platformCustomer = null)
    {
        $platformCustomer = $platformCustomer ? : $this->platformCustomerManager->getCustomerById($customerId);
        if ($subscription->getCustomerId() != $platformCustomer->getId()) {
            throw new AuthorizationException(__('Forbidden action.'));
        }
    }
}
