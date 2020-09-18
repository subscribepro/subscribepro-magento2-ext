<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;
use Swarming\SubscribePro\Model\Config\Source\CartRuleCombine;

class ItemSubscriptionDiscount
{
    const KEY_DISCOUNT_DESCRIPTION = 'subscription';

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Swarming\SubscribePro\Helper\SalesRuleValidator
     */
    protected $salesRuleValidatorHelper;

    /**
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Swarming\SubscribePro\Helper\SalesRuleValidator $salesRuleValidatorHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Swarming\SubscribePro\Helper\SalesRuleValidator $salesRuleValidatorHelper
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->platformProductManager = $platformProductManager;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->priceCurrency = $priceCurrency;
        $this->salesRuleValidatorHelper = $salesRuleValidatorHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param float $itemBasePrice
     * @param callable $rollbackCallback
     */
    public function processSubscriptionDiscount(QuoteItem $item, $itemBasePrice, callable $rollbackCallback)
    {
        $storeId = $item->getQuote()->getStoreId();
        $baseCartDiscount = $item->getBaseDiscountAmount();

        $platformProduct = $this->getPlatformProduct($item);
        $baseSubscriptionDiscount = $subscriptionDiscount = $this->priceCurrency->convertAndRound($this->getBaseSubscriptionDiscount($platformProduct, $itemBasePrice, $item->getQty()), $storeId);

        if ($this->isOnlySubscriptionDiscount($baseSubscriptionDiscount, $baseCartDiscount, $storeId)) {
            $rollbackCallback($item);
            $this->setSubscriptionDiscount($item, $subscriptionDiscount, $baseSubscriptionDiscount);
            $this->addDiscountDescription($item);
        } elseif ($this->isCombineDiscounts($storeId)) {
            $this->addSubscriptionDiscount($item, $subscriptionDiscount, $baseSubscriptionDiscount);
            $this->addDiscountDescription($item);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param $subscriptionDiscount
     * @param $baseSubscriptionDiscount
     */
    protected function setSubscriptionDiscount(QuoteItem $item, $subscriptionDiscount, $baseSubscriptionDiscount)
    {
        $item->setDiscountAmount($subscriptionDiscount);
        $item->setBaseDiscountAmount($baseSubscriptionDiscount);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param float $subscriptionDiscount
     * @param float $baseSubscriptionDiscount
     */
    protected function addSubscriptionDiscount(QuoteItem $item, $subscriptionDiscount, $baseSubscriptionDiscount)
    {
        $newDiscountAmount = $item->getDiscountAmount() + $subscriptionDiscount;
        $item->setDiscountAmount($newDiscountAmount);

        $newBaseDiscountAmount = $item->getBaseDiscountAmount() + $baseSubscriptionDiscount;
        $item->setBaseDiscountAmount($newBaseDiscountAmount);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     */
    protected function addDiscountDescription(QuoteItem $item)
    {
        $discountDescriptions = $item->getAddress()->getDiscountDescriptionArray();
        $discountDescriptions[self::KEY_DISCOUNT_DESCRIPTION] = __('Subscription');
        $item->getAddress()->setDiscountDescriptionArray($discountDescriptions);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     */
    protected function getPlatformProduct(QuoteItem $item)
    {
        $sku = $item->getProduct()->getData(ProductInterface::SKU);
        return $this->platformProductManager->getProduct($sku, $item->getQuote()->getStore()->getWebsiteId());
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param float $itemBasePrice
     * @param float $qty
     * @return float
     */
    protected function getBaseSubscriptionDiscount($platformProduct, $itemBasePrice, $qty)
    {
        return $this->salesRuleValidatorHelper->getBaseSubscriptionDiscount(
            $platformProduct->getIsDiscountPercentage(),
            $platformProduct->getDiscount(),
            $itemBasePrice,
            $qty
        );
    }

    /**
     * @param string $storeId
     * @param float $baseSubscriptionDiscount
     * @param float $baseCartDiscount
     * @return bool
     */
    protected function isOnlySubscriptionDiscount($baseSubscriptionDiscount, $baseCartDiscount, $storeId)
    {
        return $this->salesRuleValidatorHelper->isOnlySubscriptionDiscount(
            $baseSubscriptionDiscount,
            $baseCartDiscount,
            $this->subscriptionDiscountConfig->getCartRuleCombineType($storeId)
        );
    }

    /**
     * @param string $storeId
     * @return bool
     */
    protected function isCombineDiscounts($storeId)
    {
        return $this->salesRuleValidatorHelper->isCombineDiscounts(
            $this->subscriptionDiscountConfig->getCartRuleCombineType($storeId)
        );
    }
}
