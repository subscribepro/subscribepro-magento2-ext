<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;
use Swarming\SubscribePro\Model\Config\Source\CartRuleCombine;

class ItemSubscriptionDiscount
{
    const QUOTE_ITEM_RULES = 'quoteItemRules';
    const QUOTE_RULES = 'quoteRules';
    const ADDRESS_RULES = 'addressRules';

    const DISCOUNT_DESCRIPTIONS_KEY = 'subscription';

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductService;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxData;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->platformProductService = $platformProductService;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->taxData = $taxData;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array $appliedRuleIds
     * @param array $discountDescriptions
     */
    public function processSubscriptionDiscount(QuoteItem $item, array $appliedRuleIds, array $discountDescriptions)
    {
        $platformProduct = $this->getPlatformProduct($item);
        $baseSubscriptionDiscount = $this->getBaseSubscriptionDiscount($item, $platformProduct);
        $baseCartDiscount = $item->getBaseDiscountAmount();

        $storeId = $item->getQuote()->getStore()->getStoreId();
        $subscriptionDiscount = $this->priceCurrency->convertAndRound($baseSubscriptionDiscount, $storeId);

        $websiteId = $item->getQuote()->getStore()->getWebsiteId();
        if ($this->isOnlySubscriptionDiscount($websiteId, $baseSubscriptionDiscount, $baseCartDiscount)) {
            $this->rollbackCartRulesAndDescriptions($item, $appliedRuleIds, $discountDescriptions);
            $this->setSubscriptionDiscount($item, $subscriptionDiscount, $baseSubscriptionDiscount);
            $this->addDiscountDescription($item);
        } else if ($this->isCombineDiscounts($websiteId)) {
            $this->addSubscriptionDiscount($item, $subscriptionDiscount, $baseSubscriptionDiscount);
            $this->addDiscountDescription($item);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array $appliedRuleIds
     * @param array $discountDescriptions
     */
    protected function rollbackCartRulesAndDescriptions(QuoteItem $item, array $appliedRuleIds, array $discountDescriptions)
    {
        $item->setAppliedRuleIds($appliedRuleIds[self::QUOTE_ITEM_RULES]);
        $item->getAddress()->setAppliedRuleIds($appliedRuleIds[self::ADDRESS_RULES]);
        $item->getQuote()->setAppliedRuleIds($appliedRuleIds[self::QUOTE_RULES]);

        $item->getAddress()->setDiscountDescriptionArray($discountDescriptions);
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
        $discountDescriptions[self::DISCOUNT_DESCRIPTIONS_KEY] = __('Subscription');
        $item->getAddress()->setDiscountDescriptionArray($discountDescriptions);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     */
    protected function getPlatformProduct(QuoteItem $item)
    {
        $product = $this->quoteItemHelper->getProduct($item);
        return $this->platformProductService->getProduct($product->getSku());
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @return float
     */
    protected function getBaseSubscriptionDiscount(QuoteItem $item, $platformProduct)
    {
        if($platformProduct->getIsDiscountPercentage()) {
            $subscriptionDiscount = $platformProduct->getDiscount() * $this->getItemDiscountablePrice($item) * $item->getQty();
        } else {
            $subscriptionDiscount = $platformProduct->getDiscount() * $item->getQty();
        }
        return $subscriptionDiscount;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return float
     */
    protected function getItemDiscountablePrice(QuoteItem $item)
    {
        $storeId = $item->getStore()->getStoreId();
        return $this->taxData->discountTax($storeId) ? $item->getBasePriceInclTax() : $item->getBasePrice();
    }

    /**
     * @param int $websiteId
     * @param float $baseSubscriptionDiscount
     * @param float $baseCartDiscount
     * @return bool
     */
    protected function isOnlySubscriptionDiscount($websiteId, $baseSubscriptionDiscount, $baseCartDiscount)
    {
        $result = false;
        switch($this->subscriptionDiscountConfig->getCartRuleCombineType($websiteId)) {
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
     * @param int $websiteId
     * @return bool
     */
    protected function isCombineDiscounts($websiteId)
    {
        return $this->subscriptionDiscountConfig->getCartRuleCombineType($websiteId)
            == CartRuleCombine::TYPE_COMBINE_SUBSCRIPTION;
    }
}
