<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;
use Swarming\SubscribePro\Model\Config\Source\CartRuleCombine;
use Magento\Catalog\Api\Data\ProductInterface;

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
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->platformProductManager = $platformProductManager;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->priceCurrency = $priceCurrency;
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
        $baseSubscriptionDiscount = $this->getBaseSubscriptionDiscount($platformProduct, $itemBasePrice, $item->getQty());
        $subscriptionDiscount = $this->priceCurrency->convertAndRound($baseSubscriptionDiscount, $storeId);

        if ($this->isOnlySubscriptionDiscount($baseSubscriptionDiscount, $baseCartDiscount, $storeId)) {
            $rollbackCallback($item);
            $this->setSubscriptionDiscount($item, $subscriptionDiscount, $baseSubscriptionDiscount);
            $this->addDiscountDescription($item);
        } else if ($this->isCombineDiscounts($storeId)) {
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
        if($platformProduct->getIsDiscountPercentage()) {
            $subscriptionDiscount = $platformProduct->getDiscount() * $itemBasePrice * $qty;
        } else {
            $subscriptionDiscount = $platformProduct->getDiscount() * $qty;
        }

        return $subscriptionDiscount;
    }

    /**
     * @param string $storeId
     * @param float $baseSubscriptionDiscount
     * @param float $baseCartDiscount
     * @return bool
     */
    protected function isOnlySubscriptionDiscount($baseSubscriptionDiscount, $baseCartDiscount, $storeId)
    {
        $result = false;
        switch($this->subscriptionDiscountConfig->getCartRuleCombineType($storeId)) {
            case CartRuleCombine::TYPE_APPLY_GREATEST:
                if($baseSubscriptionDiscount >= $baseCartDiscount) {
                    $result = true;
                }
                break;
            case CartRuleCombine::TYPE_APPLY_LEAST:
                if($baseSubscriptionDiscount <= $baseCartDiscount) {
                    $result = true;
                }
                break;
            case CartRuleCombine::TYPE_APPLY_CART_DISCOUNT: /* Only If no cart rules applied */
                if($baseCartDiscount == 0) {
                    $result = true;
                }
                break;
            case CartRuleCombine::TYPE_APPLY_SUBSCRIPTION:
                $result = true;
                break;
            default:
                $result = false;
                break;
        }
        return $result;
    }

    /**
     * @param string $storeId
     * @return bool
     */
    protected function isCombineDiscounts($storeId)
    {
        return $this->subscriptionDiscountConfig->getCartRuleCombineType($storeId)
            == CartRuleCombine::TYPE_COMBINE_SUBSCRIPTION;
    }
}
