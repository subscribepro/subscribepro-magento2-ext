<?php

namespace Swarming\SubscribePro\Plugin\Product;

use SubscribePro\Service\Product\ProductInterface;
use Swarming\SubscribePro\Model\Subscription\OptionItem as SubscriptionOptionItem;

class Configuration
{
    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\Product $productHelper,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
    ) {
        $this->productHelper = $productHelper;
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * @param \Magento\Catalog\Helper\Product\Configuration $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
     * @return string[]
     */
    public function aroundGetCustomOptions(
        \Magento\Catalog\Helper\Product\Configuration $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
    ) {
        $options = [];
        if ($this->productHelper->isSubscriptionEnabled($item->getProduct()) && !$item instanceof SubscriptionOptionItem) {
            $options = $this->getSubscriptionOptions($item);
        }

        return array_merge($proceed($item), $options);
    }

    /**
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
     * @return string[]
     */
    protected function getSubscriptionOptions($item)
    {
        $options = [];
        $subscriptionOption = $this->quoteItemHelper->getSubscriptionOption($item);
        if (ProductInterface::SO_ONETIME_PURCHASE == $subscriptionOption) {
            $options[] = [
                'label' => (string)__('Delivery'),
                'value' => (string)__('One Time')
            ];
        } elseif (ProductInterface::SO_SUBSCRIPTION == $subscriptionOption) {
            $subscriptionInterval = $this->quoteItemHelper->getSubscriptionInterval($item);
            $options[] = [
                'label' => (string)__('Regular Delivery'),
                'value' => (string)__($subscriptionInterval)
            ];
        }
        return $options;
    }
}
