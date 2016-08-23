<?php

namespace Swarming\SubscribePro\Plugin\Product;

use Magento\Catalog\Block\Product\View as ProductView;

class Subscription
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Helper\Product $productHelper
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->productHelper = $productHelper;
    }

    /**
     * @param \Magento\Catalog\Block\Product\View $subject
     * @param bool $hasOptions
     * @return bool
     */
    public function afterHasOptions(ProductView $subject, $hasOptions)
    {
        if ($hasOptions) {
            return true;
        }

        return $this->subscriptionDiscountConfig->isEnabled()
        && $this->productHelper->isSubscriptionEnabled($subject->getProduct());
    }
}
