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
     * @var \Swarming\SubscribePro\Platform\Helper\Product
     */
    protected $platformProductHelper;

    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Customer
     */
    protected $platformCustomerHelper;

    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Subscription
     */
    protected $platformSubscriptionHelper;

    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Address
     */
    protected $platformAddressHelper;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Swarming\SubscribePro\Platform\Link\Subscription
     */
    protected $linkSubscription;

    /**
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $design;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper
     * @param \Swarming\SubscribePro\Platform\Helper\Customer $platformCustomerHelper
     * @param \Swarming\SubscribePro\Platform\Helper\Subscription $platformSubscriptionHelper
     * @param \Swarming\SubscribePro\Platform\Helper\Address $platformAddressHelper
     * @param \Swarming\SubscribePro\Platform\Link\Subscription $linkSubscription
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper,
        \Swarming\SubscribePro\Platform\Helper\Customer $platformCustomerHelper,
        \Swarming\SubscribePro\Platform\Helper\Subscription $platformSubscriptionHelper,
        \Swarming\SubscribePro\Platform\Helper\Address $platformAddressHelper,
        \Swarming\SubscribePro\Platform\Link\Subscription $linkSubscription,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Framework\View\DesignInterface $design,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->platformProductHelper = $platformProductHelper;
        $this->platformCustomerHelper = $platformCustomerHelper;
        $this->platformSubscriptionHelper = $platformSubscriptionHelper;
        $this->platformAddressHelper = $platformAddressHelper;
        $this->addressRepository = $addressRepository;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->linkSubscription = $linkSubscription;
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
            $platformCustomer = $this->platformCustomerHelper->getCustomer($customerId);
            $subscriptions = $this->platformSubscriptionHelper->loadSubscriptionsByCustomer($platformCustomer->getId());
            if (empty($subscriptions)) {
                throw new NoSuchEntityException();
            }
            $this->linkSubscription->linkSubscriptionsProduct($subscriptions);
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
     * @param int $subscriptionId
     * @param int $qty
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updateQty($customerId, $subscriptionId, $qty)
    {
        try {
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $platformProduct = $this->platformProductHelper->getProduct($subscription->getProductSku());
            if ($platformProduct->getMinQty() > $qty || $platformProduct->getMaxQty() < $qty) {
                throw new LocalizedException(__(
                    'Invalid quantity, it must be in range from %1 to %2.',
                    $platformProduct->getMinQty(),
                    $platformProduct->getMaxQty()
                ));
            }

            $subscription->setQty($qty);
            $this->platformSubscriptionHelper->saveSubscription($subscription);
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
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $subscription->setInterval($interval);
            $this->platformSubscriptionHelper->saveSubscription($subscription);
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
        if ($nextOrderDate < date('Y-m-d', strtotime('+2 days'))) {
            throw new LocalizedException(__('Invalid next order date, it must be not earlier than 2 days in the future'));
        }

        try {
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $subscription->setNextOrderDate($nextOrderDate);
            $this->platformSubscriptionHelper->saveSubscription($subscription);
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
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function updatePaymentProfile($customerId, $subscriptionId, $paymentProfileId)
    {
        try {
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $subscription->setPaymentProfileId($paymentProfileId);
            $subscription = $this->platformSubscriptionHelper->saveSubscription($subscription);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while updating payment profile.'));
        }

        return $subscription->getPaymentProfile();
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
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $saveInAddressBook = $address->getSaveInAddressBook();
            $address = $address->exportCustomerAddress();
            $address->setCustomerId($customerId);
            $platformCustomer = $this->platformCustomerHelper->getCustomer($customerId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $platformAddress = $this->platformAddressHelper->findOrSaveAddress($address, $platformCustomer);
            $subscription->setShippingAddressId($platformAddress->getId());
            $this->platformSubscriptionHelper->saveSubscription($subscription);
            if ($saveInAddressBook) {
                $this->addressRepository->save($address);
            }
            $platformAddress->setAddressInline($this->getCustomerAddressInline($address));
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
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $this->platformSubscriptionHelper->skip($subscriptionId);
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
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
        try {
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $this->platformSubscriptionHelper->cancel($subscriptionId);
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
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $this->platformSubscriptionHelper->pause($subscriptionId);
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
            $subscription = $this->platformSubscriptionHelper->loadSubscription($subscriptionId);
            $this->checkSubscriptionOwner($subscription, $customerId);

            $this->platformSubscriptionHelper->restart($subscriptionId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The subscription is not found.'));
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('An error occurred while pausing subscription.'));
        }
        return true;
    }

    /**
     * @param \SubscribePro\Service\Subscription\SubscriptionInterface $subscription
     * @param $customerId
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    protected function checkSubscriptionOwner($subscription, $customerId)
    {
        $platformCustomer = $this->platformCustomerHelper->getCustomer($customerId);
        if ($subscription->getCustomerId() != $platformCustomer->getId()) {
            throw new AuthorizationException(__('Forbidden action.'));
        }
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    protected function getCustomerAddressInline($address)
    {
        $builtOutputAddressData = $this->addressMapper->toFlatArray($address);
        return $this->addressConfig
            ->getFormatByCode(\Magento\Customer\Model\Address\Config::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }
}
