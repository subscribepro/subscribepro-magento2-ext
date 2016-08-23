<?php

namespace Swarming\SubscribePro\Plugin\SalesRule;

use Magento\SalesRule\Model\Validator as SalesRuleValidator;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;
use Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount;

class Validator
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount
     */
    protected $itemSubscriptionDiscount;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxData;

    /**
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount $itemSubscriptionDiscount
     * @param \Magento\Tax\Helper\Data $taxData
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount $itemSubscriptionDiscount,
        \Magento\Tax\Helper\Data $taxData
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->itemSubscriptionDiscount = $itemSubscriptionDiscount;
        $this->taxData = $taxData;
    }

    /**
     * @param \Magento\SalesRule\Model\Validator $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return \Magento\SalesRule\Model\Validator
     */
    public function aroundProcess(SalesRuleValidator $subject, \Closure $proceed, QuoteItem $item)
    {
        $appliedRuleIds = array(
            ItemSubscriptionDiscount::QUOTE_ITEM_RULES => $item->getAppliedRuleIds(),
            ItemSubscriptionDiscount::QUOTE_RULES => $item->getQuote()->getAppliedRuleIds(),
            ItemSubscriptionDiscount::ADDRESS_RULES => $item->getAddress()->getAppliedRuleIds(),
        );
        $discountDescriptions = (array)$item->getAddress()->getDiscountDescriptionArray();
        $isCatalogDiscountApplied = $this->isCatalogDiscountApplied($item);

        $result = $proceed($item);

        $websiteId = $item->getQuote()->getStore()->getWebsiteId();
        $storeCode = $item->getQuote()->getStore()->getCode();
        if ($this->subscriptionDiscountConfig->isEnabled($websiteId)
            &&
            ($this->quoteItemHelper->isSubscriptionEnabled($item) || $this->quoteItemHelper->isFulfilsSubscription($item))
            &&
            (!$isCatalogDiscountApplied || $this->subscriptionDiscountConfig->isApplyDiscountToCatalogPrice($storeCode))
        ) {
            $this->itemSubscriptionDiscount->processSubscriptionDiscount($item, $appliedRuleIds, $discountDescriptions);
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    protected function isCatalogDiscountApplied(QuoteItem $item)
    {
        $baseOriginalPrice = $item->getBaseOriginalPrice();
        $basePrice = $this->taxData->priceIncludesTax() ? $item->getBasePriceInclTax() : $item->getBasePrice();
        return $basePrice < $baseOriginalPrice;
    }
}
