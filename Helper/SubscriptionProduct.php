<?php

namespace Swarming\SubscribePro\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Swarming\SubscribePro\Block\Product\Subscription as SubscriptionBlock;

class SubscriptionProduct
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Swarming\SubscribePro\Model\Subscription\OptionItemManager
     */
    protected $subscriptionOptionItemManager;

    /**
     * @var \Magento\Catalog\Model\Product\Url
     */
    protected $productUrlModel;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Catalog\Helper\Product\ConfigurationPool
     */
    protected $productConfigurationPool;

    /**
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    protected $taxCalculation;

    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\InspectorInterface
     */
    protected $catalogRuleInspector;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Model\Subscription\OptionItemManager $subscriptionOptionItemManager
     * @param \Magento\Catalog\Model\Product\Url $productUrlModel
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Catalog\Helper\Product\ConfigurationPool $productConfigurationPool
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param \Swarming\SubscribePro\Model\CatalogRule\InspectorInterface $catalogRuleInspector
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Model\Subscription\OptionItemManager $subscriptionOptionItemManager,
        \Magento\Catalog\Model\Product\Url $productUrlModel,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Helper\Product\ConfigurationPool $productConfigurationPool,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Swarming\SubscribePro\Model\CatalogRule\InspectorInterface $catalogRuleInspector,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->platformProductManager = $platformProductManager;
        $this->subscriptionOptionItemManager = $subscriptionOptionItemManager;
        $this->productUrlModel = $productUrlModel;
        $this->imageHelper = $imageHelper;
        $this->productConfigurationPool = $productConfigurationPool;
        $this->taxCalculation = $taxCalculation;
        $this->catalogRuleInspector = $catalogRuleInspector;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface[] $subscriptions
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionInterface[]
     */
    public function linkProducts(array $subscriptions)
    {
        foreach ($subscriptions as $subscription) {
            try {
                $platformProduct = $this->platformProductManager->getProduct($subscription->getProductSku());
            } catch (NoSuchEntityException $e) {
                continue;
            }

            $subscriptionOptionItem = $this->subscriptionOptionItemManager->getSubscriptionOptionItem($subscription);

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $subscriptionOptionItem->getProduct();
            if ($product) {
                $platformProduct->setUrl($this->getProductUrl($product));
                $platformProduct->setImageUrl($this->getProductImageUrl($product));
                $platformProduct->setOptionList($this->getProductOptionList($subscriptionOptionItem));
                $platformProduct->setPrice($this->getProductPrice($product, $subscription->getQty()));
                $platformProduct->setIsCatalogRuleApplied($this->catalogRuleInspector->isApplied($product));
                $platformProduct->setTaxRate($this->getProductTaxRate($product));
                $this->processSubscriptionDiscount($platformProduct);
            } else {
                $platformProduct->setImageUrl($this->getImagePlaceholderUrl());
                $platformProduct->setPrice(0);
            }
            $subscription->setProduct($platformProduct);
        }

        return $subscriptions;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @return string|null
     */
    protected function getProductUrl($product)
    {
        return $product->isVisibleInSiteVisibility() ? $this->productUrlModel->getProductUrl($product) : null;
    }

    /**
     * @return string
     */
    protected function getImagePlaceholderUrl()
    {
        return $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @return string
     */
    protected function getProductImageUrl($product)
    {
        return $this->imageHelper->init($product, 'product_thumbnail_image')->getUrl();
    }

    /**
     * @param \Swarming\SubscribePro\Model\Subscription\OptionItem $subscriptionOptionItem
     * @return array
     */
    protected function getProductOptionList($subscriptionOptionItem)
    {
        $productType = $subscriptionOptionItem->getProduct()->getTypeId();
        $productConfiguration = $this->productConfigurationPool->getByProductType($productType);
        $options = $productConfiguration->getOptions($subscriptionOptionItem);
        return $options;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param int $qty
     * @return float
     */
    protected function getProductPrice($product, $qty)
    {
        $productPrice = $product->getFinalPrice($qty);
        return $this->priceCurrency->convertAndRound($productPrice);
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @return float
     */
    protected function getProductTaxRate($product)
    {
        $productTaxClassId = $product->getCustomAttribute(SubscriptionBlock::TAX_CLASS_ID)->getValue();
        return $this->taxCalculation->getCalculatedRate($productTaxClassId);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     */
    protected function processSubscriptionDiscount($platformProduct)
    {
        $discount = $platformProduct->getIsDiscountPercentage()
            ? $platformProduct->getDiscount()
            : $this->priceCurrency->convertAndRound($platformProduct->getDiscount());
        $platformProduct->setDiscount($discount);
    }
}
