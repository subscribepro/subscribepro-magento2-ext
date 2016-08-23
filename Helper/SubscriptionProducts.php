<?php

namespace Swarming\SubscribePro\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Model\Config as TaxConfig;
use Swarming\SubscribePro\Block\Product\Subscription as SubscriptionBlock;

class SubscriptionProducts
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Catalog\Model\Product\Url
     */
    protected $productUrlModel;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Model\Product\Url $productUrlModel
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\Product\Url $productUrlModel,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->platformProductManager = $platformProductManager;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productUrlModel = $productUrlModel;
        $this->imageHelper = $imageHelper;
        $this->taxCalculation = $taxCalculation;
        $this->priceCurrency = $priceCurrency;
    }
    
    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface[] $subscriptions
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionInterface[]
     */
    public function linkProducts(array $subscriptions)
    {
        $magentoProducts = $this->getMagentoProducts($subscriptions);

        foreach ($subscriptions as $subscription) {
            try {
                $platformProduct = $this->platformProductManager->getProduct($subscription->getProductSku());
            } catch (NoSuchEntityException $e) {
                continue;
            }

            if (!$platformProduct->getIsDiscountPercentage()) {
                $discount = $this->priceCurrency->convertAndRound($platformProduct->getDiscount(), true);
                $platformProduct->setDiscount($discount);
            }

            $platformProduct
                ->setUrl($this->getProductUrl($magentoProducts[$subscription->getProductSku()]))
                ->setImageUrl($this->getProductImageUrl($magentoProducts[$subscription->getProductSku()]))
                ->setPrice($this->getProductPrice($magentoProducts[$subscription->getProductSku()]))
                ->setFinalPrice($this->getProductFinalPrice($magentoProducts[$subscription->getProductSku()]))
                ->setTaxRate($this->getProductTaxRate($magentoProducts[$subscription->getProductSku()]));

            $subscription->setProduct($platformProduct);
        }

        return $subscriptions;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface[] $subscriptions
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    protected function getMagentoProducts(array $subscriptions)
    {
        $productsSku = $this->getProductsSku($subscriptions);
        $this->searchCriteriaBuilder->addFilter(ProductInterface::SKU, $productsSku, 'in');
        $productList = $this->productRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        $products = array_fill_keys($productsSku, null);
        foreach ($productList as $product) {
            $products[$product->getSku()] = $product;
        }
        return $products;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface[] $subscriptions
     * @return string[]
     */
    protected function getProductsSku(array $subscriptions)
    {
        $productsSku = array_map(function(SubscriptionInterface $subscription) {
            return $subscription->getProductSku();
        }, $subscriptions);
        return array_unique($productsSku);
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $magentoProduct
     * @return string
     */
    protected function getProductUrl($magentoProduct = null)
    {
        return $magentoProduct ? $this->productUrlModel->getProductUrl($magentoProduct) : '';
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $magentoProduct
     * @return string
     */
    protected function getProductImageUrl($magentoProduct = null)
    {
        return $magentoProduct
            ? $this->imageHelper->init($magentoProduct, 'product_thumbnail_image')->getUrl()
            : $this->imageHelper->getDefaultPlaceholderUrl('thumbnail');
    }
    
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $magentoProduct
     * @return float
     */
    protected function getProductPrice($magentoProduct = null)
    {
        return $magentoProduct ? $magentoProduct->getPriceInfo()->getPrice(RegularPrice::PRICE_CODE)->getValue() : 0;
    }
    
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $magentoProduct
     * @return float
     */
    protected function getProductFinalPrice($magentoProduct = null)
    {
        return $magentoProduct ? $magentoProduct->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getValue() : 0;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $magentoProduct
     * @return float
     */
    protected function getProductTaxRate($magentoProduct = null)
    {
        if (!$magentoProduct) {
            return 0;
        }

        return $this->taxCalculation->getCalculatedRate($magentoProduct->getCustomAttribute(SubscriptionBlock::TAX_CLASS_ID)->getValue());
    }
}
