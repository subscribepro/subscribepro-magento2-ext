<?php

namespace Swarming\SubscribePro\Model\Quote\QuoteItem;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProductType;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use SubscribePro\Service\Subscription\Subscription;
use SubscribePro\Service\Subscription\SubscriptionInterface;
use Swarming\SubscribePro\Api\Data\AddressInterface;

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
     * @param $quoteItem
     * @param $platformCustomerId
     * @param $paymentProfileId
     * @param $shippingAddress
     * @param $billingAddress
     * @return false|string|null
     * @throws \Exception
     */
    public function create($quoteItem, $platformCustomerId, $paymentProfileId, $shippingAddress = null, $billingAddress = null)
    {
        $quote = $quoteItem->getQuote();
        $store = $quote->getStore();
        $productSku = $this->getProductSku($quoteItem);
        $storeTimezone = new \DateTimeZone($store->getConfig('general/locale/timezone'));
        try {
            /** @var \Swarming\SubscribePro\Model\Subscription $subscription */
            $subscription = $this->platformSubscriptionService->createSubscription();
            $subscription->setCustomerId($platformCustomerId);
            $subscription->setPaymentProfileId($paymentProfileId);
            $subscription->setProductSku($productSku);
            $subscription->setProductOption($this->productOptionHelper->getProductOption($quoteItem));
            $subscription->setQty($quoteItem->getQty());
            $subscription->setUseFixedPrice(false);
            $subscription->setInterval($this->quoteItemHelper->getSubscriptionInterval($quoteItem));
            $subscription->setNextOrderDate($this->dateTimeFactory->create('now', $storeTimezone)->format('Y-m-d'));
            $subscription->setFirstOrderAlreadyCreated(true);
            $subscription->setMagentoStoreCode($store->getCode());
            $subscription->setSendCustomerNotificationEmail(true);

            $subscription->setRequiresShipping((bool)$shippingAddress);
            if ($shippingAddress) {
                $this->importShippingAddress($subscription, $shippingAddress);
                $subscription->setMagentoShippingMethodCode($shippingAddress->getShippingMethod());
            } else {
                $subscription->setShippingAddress(null);
            }

            if ($billingAddress) {
                $this->importBillingAddress($subscription, $billingAddress);
            } else {
                $subscription->setBillingAddress(null);
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
                ['subscription' => $subscription, 'quote_item' => $quoteItem]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
        return $subscription->getId();
    }

    /**
     * Gets the product sku for the quote item
     * If enabled, grabs the child SKU when configurable
     *
     * @param Item $quoteItem
     * @return string
     */
    protected function getProductSku($quoteItem)
    {
        $product = $quoteItem->getProduct();

        if ($quoteItem->getProductType() === ConfigurableProductType::TYPE_CODE
            && $this->subscriptionOptionsConfig->isChildSkuForConfigurableEnabled()
            && $quoteItem->getOptionByCode('simple_product')
        ) {
            /** @var Option $option */
            $option = $quoteItem->getOptionByCode('simple_product');
            if ($option !== null) {
                $product = $option->getProduct();
            }
        }

        return $product->getData(ProductInterface::SKU);
    }

    /**
     * @param \SubscribePro\Service\Address\AddressInterface $subAddress
     * @param Address $quoteAddress
     */
    private function buildAddressData(&$subAddress, $quoteAddress)
    {
        $subAddress->setFirstName($quoteAddress->getFirstname());
        $subAddress->setLastName($quoteAddress->getLastname());
        $subAddress->setCompany($quoteAddress->getCompany());
        $subAddress->setStreet1($quoteAddress->getStreetLine(1));
        $subAddress->setStreet2($quoteAddress->getStreetLine(2));
        $subAddress->setStreet3($quoteAddress->getStreetLine(3));
        $subAddress->setCity($quoteAddress->getCity());
        $subAddress->setRegion($quoteAddress->getRegionCode());
        $subAddress->setPostcode($quoteAddress->getPostcode());
        $subAddress->setCountry($quoteAddress->getCountryId());
        $subAddress->setPhone($quoteAddress->getTelephone());
        return $subAddress;
    }

    /**
     * @param SubscriptionInterface $subscription
     * @param Address $address
     */
    protected function importShippingAddress($subscription, $address)
    {
        $shippingAddress = $subscription->getShippingAddress();
        if ($shippingAddress !== null) {
            $this->buildAddressData($shippingAddress, $address);
        }
    }

    /**
     * @param SubscriptionInterface $subscription
     * @param Address $address
     */
    protected function importBillingAddress($subscription, $address)
    {
        /** @var Subscription $subscription */
        $billingAddress = $subscription->getBillingAddress();
        if ($billingAddress !== null) {
            $this->buildAddressData($billingAddress, $address);
        }
    }
}
