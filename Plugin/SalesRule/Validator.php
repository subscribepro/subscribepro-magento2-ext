<?php

namespace Swarming\SubscribePro\Plugin\SalesRule;

use Magento\SalesRule\Model\Validator as SalesRuleValidator;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;

class Validator
{
    const QUOTE_ITEM_RULES = 'quoteItemRules';
    const QUOTE_RULES = 'quoteRules';
    const ADDRESS_RULES = 'addressRules';

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount
     */
    protected $itemSubscriptionDiscount;

    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\InspectorInterface
     */
    protected $catalogRuleInspector;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount $itemSubscriptionDiscount
     * @param \Swarming\SubscribePro\Model\CatalogRule\InspectorInterface $catalogRuleInspector
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount $itemSubscriptionDiscount,
        \Swarming\SubscribePro\Model\CatalogRule\InspectorInterface $catalogRuleInspector,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->itemSubscriptionDiscount = $itemSubscriptionDiscount;
        $this->catalogRuleInspector = $catalogRuleInspector;
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * @param \Magento\SalesRule\Model\Validator $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return \Magento\SalesRule\Model\Validator
     */
    public function aroundProcess(SalesRuleValidator $subject, \Closure $proceed, QuoteItem $item)
    {
        $appliedRuleIds = [
            self::QUOTE_ITEM_RULES => $item->getAppliedRuleIds(),
            self::QUOTE_RULES => $item->getQuote()->getAppliedRuleIds(),
            self::ADDRESS_RULES => $item->getAddress()->getAppliedRuleIds(),
        ];
        $discountDescriptions = (array)$item->getAddress()->getDiscountDescriptionArray();

        $result = $proceed($item);

        $websiteId = $item->getQuote()->getStore()->getWebsiteId();
        if (!$this->subscriptionDiscountConfig->isEnabled($websiteId)) {
            return $result;
        }

        if (!$this->quoteItemHelper->hasSubscription($item)) {
            return $result;
        }

        $storeCode = $item->getQuote()->getStore()->getCode();
        if ($this->catalogRuleInspector->isApplied($item->getProduct())
            && !$this->subscriptionDiscountConfig->isApplyDiscountToCatalogPrice($storeCode)
        ) {
            return $result;
        }

        $this->itemSubscriptionDiscount->processSubscriptionDiscount(
            $item,
            $subject->getItemBasePrice($item),
            $this->getRollbackCallback($appliedRuleIds, $discountDescriptions)
        );

        return $result;
    }

    /**
     * @param array $appliedRuleIds
     * @param array|null $discountDescriptions
     * @return callable
     * @codeCoverageIgnore
     */
    protected function getRollbackCallback($appliedRuleIds, $discountDescriptions)
    {
        return function (QuoteItem $item) use ($appliedRuleIds, $discountDescriptions) {
            $item->setAppliedRuleIds($appliedRuleIds[self::QUOTE_ITEM_RULES]);
            $item->getAddress()->setAppliedRuleIds($appliedRuleIds[self::ADDRESS_RULES]);
            $item->getQuote()->setAppliedRuleIds($appliedRuleIds[self::QUOTE_RULES]);

            $item->getAddress()->setDiscountDescriptionArray($discountDescriptions);
        };
    }
}
