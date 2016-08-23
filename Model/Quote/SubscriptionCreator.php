<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class SubscriptionCreator
{
    const CREATED_SUBSCRIPTION_IDS = 'created_subscription_ids';
    const FAILED_SUBSCRIPTION_COUNT = 'failed_subscription_count';

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionOptions
     */
    protected $subscriptionOptionsConfig;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformSubscriptionService;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManager;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $tokenManagement;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem\ProductOption
     */
    protected $quoteItemProductOption;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionsConfig
     * @param \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Helper\QuoteItem\ProductOption $quoteItemProductOption
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionsConfig,
        \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService,
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Helper\QuoteItem\ProductOption $quoteItemProductOption,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->subscriptionOptionsConfig = $subscriptionOptionsConfig;
        $this->platformSubscriptionService = $platformSubscriptionService;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->tokenManagement = $tokenManagement;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->quoteItemProductOption = $quoteItemProductOption;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    public function createSubscriptions($quote, $order)
    {
        $paymentProfileId = $this->getPaymentProfileId($order->getPayment());
        $platformCustomer = $this->platformCustomerManager->getCustomerById($quote->getCustomerId());

        $subscriptionsSuccess = [];
        $subscriptionsFail = 0;
        /** @var \Magento\Quote\Model\Quote\Address $address */
        foreach ($quote->getAllShippingAddresses() as $address) {
            foreach ($address->getAllItems() as $quoteItem) {
                if (!$this->quoteItemHelper->isSubscriptionEnabled($quoteItem)
                    || $this->quoteItemHelper->isFulfilsSubscription($quoteItem)
                ) {
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
            if (!$quoteItem->getIsVirtual()
                || !$this->quoteItemHelper->isSubscriptionEnabled($quoteItem)
                || $this->quoteItemHelper->isFulfilsSubscription($quoteItem)
            ) {
                continue;
            }
            $subscriptionId = $this->createSubscription($quoteItem, $address, $platformCustomer->getId(), $paymentProfileId);
            if ($subscriptionId) {
                $subscriptionsSuccess[] = $subscriptionId;
            } else {
                $subscriptionsFail++;
            }
        }

        return [
            self::CREATED_SUBSCRIPTION_IDS => $subscriptionsSuccess,
            self::FAILED_SUBSCRIPTION_COUNT => $subscriptionsFail
        ];
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
        $productSku = $quoteItem->getProduct()->getData(ProductInterface::SKU);
        try {
            $subscription = $this->platformSubscriptionService->createSubscription();
            $subscription->setCustomerId($platformCustomerId);
            $subscription->setPaymentProfileId($paymentProfileId);
            $subscription->setProductSku($productSku);
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
            $productOptions = $this->quoteItemProductOption->getProductOptions($quoteItem);

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
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @return string
     * @throws \Exception
     */
    protected function getPaymentProfileId($payment)
    {
        $vault = $this->tokenManagement->getByPaymentId($payment->getEntityId());
        if (!$vault || !$vault->getIsActive()) {
            throw new \Exception('The vault is not found.');
        }
        return $vault->getGatewayToken();
    }
}
