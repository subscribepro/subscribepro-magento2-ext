<?php

namespace Swarming\SubscribePro\Model\Quote\QuoteItem;

use Magento\Catalog\Api\Data\ProductInterface;

class SubscriptionCreator
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionOptions
     */
    protected $subscriptionOptionsConfig;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformSubscriptionService;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\ProductOption
     */
    protected $productOptionHelper;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionsConfig
     * @param \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Helper\ProductOption $productOptionHelper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionOptions $subscriptionOptionsConfig,
        \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Helper\ProductOption $productOptionHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->subscriptionOptionsConfig = $subscriptionOptionsConfig;
        $this->platformSubscriptionService = $platformSubscriptionService;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->productOptionHelper = $productOptionHelper;
        $this->eventManager = $eventManager;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param int $platformCustomerId
     * @param int $paymentProfileId
     * @param \Magento\Quote\Model\Quote\Address|null $address
     * @return int
     */
    public function create($quoteItem, $platformCustomerId, $paymentProfileId, $address = null)
    {
        $quote = $quoteItem->getQuote();
        $store = $quote->getStore();
        $productSku = $quoteItem->getProduct()->getData(ProductInterface::SKU);
        try {
            $subscription = $this->platformSubscriptionService->createSubscription();
            $subscription->setCustomerId($platformCustomerId);
            $subscription->setPaymentProfileId($paymentProfileId);
            $subscription->setProductSku($productSku);
            $subscription->setProductOption($this->productOptionHelper->getProductOption($quoteItem));
            $subscription->setQty($quoteItem->getQty());
            $subscription->setUseFixedPrice(false);
            $subscription->setInterval($this->quoteItemHelper->getSubscriptionInterval($quoteItem));
            $subscription->setNextOrderDate($this->dateTimeFactory->create()->format('Y-m-d'));
            $subscription->setFirstOrderAlreadyCreated(true);
            $subscription->setMagentoStoreCode($store->getCode());

            $subscription->setRequiresShipping((bool)$address);
            if ($address) {
                $this->importShippingAddress($subscription, $address);
                $subscription->setMagentoShippingMethodCode($address->getShippingMethod());
            }

            if ($this->subscriptionOptionsConfig->isAllowedCoupon($store->getCode())) {
                $subscription->setCouponCode($quote->getCouponCode());
            }

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
}
