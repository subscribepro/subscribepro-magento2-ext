<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Quote\Model\Quote\Item\AbstractItem;

class SubscriptionCreator
{
    const CREATED_SUBSCRIPTION_IDS = 'created_subscription_ids';
    const FAILED_SUBSCRIPTION_COUNT = 'failed_subscription_count';

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected  $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformSubscriptionService;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Customer
     */
    protected $platformCustomerService;

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionOptions
     */
    protected $subscriptionOptionsConfig;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $tokenManagement;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService
     * @param \Swarming\SubscribePro\Platform\Service\Customer $platformCustomerService
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionsConfig
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService,
        \Swarming\SubscribePro\Platform\Service\Customer $platformCustomerService,
        \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionsConfig,
        \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
        $this->platformSubscriptionService = $platformSubscriptionService;
        $this->platformCustomerService = $platformCustomerService;
        $this->subscriptionOptionsConfig = $subscriptionOptionsConfig;
        $this->tokenManagement = $tokenManagement;
        $this->checkoutSession = $checkoutSession;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function createSubscriptions($quote)
    {
        $paymentProfileId = $this->getPaymentProfileId($quote->getPayment());
        $platformCustomer = $this->platformCustomerService->getCustomer($quote->getCustomerId());

        $subscriptionsSuccess = [];
        $subscriptionsFail = 0;
        /** @var \Magento\Quote\Model\Quote\Address $address */
        foreach ($quote->getAllShippingAddresses() as $address) {
            foreach ($address->getAllItems() as $quoteItem) {
                if (!$this->quoteItemHelper->isSubscriptionEnabled($quoteItem)) {
                    continue;
                }
                $subscriptionId = $this->createSubscription($quoteItem, $address, $platformCustomer->getId(), $paymentProfileId);
                if ($subscriptionId) {
                    $subscriptionsSuccess[] = $subscriptionId;
                } else {
                    $subscriptionsFail++;
                }
            }
        }

        $address = $quote->getBillingAddress();
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            if (!$quoteItem->getIsVirtual() || !$this->quoteItemHelper->isSubscriptionEnabled($quoteItem)) {
                continue;
            }
            $subscriptionId = $this->createSubscription($quoteItem, $address, $platformCustomer->getId(), $paymentProfileId);
            if ($subscriptionId) {
                $subscriptionsSuccess[] = $subscriptionId;
            } else {
                $subscriptionsFail++;
            }
        }

        $this->checkoutSession->setData(self::CREATED_SUBSCRIPTION_IDS, $subscriptionsSuccess);
        $this->checkoutSession->setData(self::FAILED_SUBSCRIPTION_COUNT, $subscriptionsFail);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param int $platformCustomerId
     * @param int $paymentProfileId
     * @return int
     */
    protected function createSubscription(AbstractItem $quoteItem, $address, $platformCustomerId, $paymentProfileId)
    {
        $quote = $quoteItem->getQuote();
        $store = $quote->getStore();
        try {
            $subscription = $this->platformSubscriptionService->createSubscription();
            $subscription->setCustomerId($platformCustomerId);
            $subscription->setPaymentProfileId($paymentProfileId);
            $subscription->setProductSku($quoteItem->getProduct()->getSku());
            $subscription->setQty($quoteItem->getQty());
            $subscription->setUseFixedPrice(false);
            $subscription->setInterval($this->quoteItemHelper->getSubscriptionInterval($quoteItem));
            $subscription->setNextOrderDate(date('Y-m-d'));
            $subscription->setFirstOrderAlreadyCreated(true);
            $subscription->setMagentoStoreCode($store->getCode());
            $subscription->setMagentoShippingMethodCode($address->getShippingMethod());

            $this->importShippingAddress($subscription, $address);
            if ($this->subscriptionOptionsConfig->isAllowedCoupon($store->getCode())) {
                $subscription->setCouponCode($quote->getCouponCode());
            }

            /* TODO Add product options to subscription */

            $this->eventManager->dispatch(
                'subscribe_pro_before_create_subscription_from_quote_item',
                ['subscription' => $subscription, 'quote_item' => $quoteItem]
            );

            $this->platformSubscriptionService->saveSubscription($subscription);

            $this->eventManager->dispatch(
                'subscribe_pro_after_create_subscription_from_quote_item',
                ['subscription' => $subscription, 'quote_item' => $quoteItem]);
        } catch(\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
        return $subscription->getId();
    }

    /**
     * @param \SubscribePro\Service\Subscription\SubscriptionInterface $subscription
     * @param \Magento\Quote\Model\Quote\Address $address
     */
    protected function importShippingAddress($subscription, $address)
    {
        $shippingAddress = $subscription->getShippingAddress();
        $shippingAddress->setFirstName($address->getFirstname());
        $shippingAddress->setLastName($address->getLastname());
        $shippingAddress->setStreet1($address->getStreetLine(1));
        $shippingAddress->setStreet2($address->getStreetLine(2));
        $shippingAddress->setCity($address->getCity());
        $shippingAddress->setRegion($address->getRegionCode());
        $shippingAddress->setPostcode($address->getPostcode());
        $shippingAddress->setCountry($address->getCountryId());
        $shippingAddress->setPhone($address->getTelephone());
    }

    /**
     * @param \Magento\Quote\Model\Quote\Payment $payment
     * @return string
     * @throws \Exception
     */
    protected function getPaymentProfileId($payment)
    {
        $vault = $this->tokenManagement->getByPaymentId($payment->getId());
        if (!$vault || !$vault->getIsActive()) {
            throw new \Exception('The vault is not found.');
        }
        return $vault->getGatewayToken();
    }
}
