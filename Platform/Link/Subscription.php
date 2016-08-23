<?php

namespace Swarming\SubscribePro\Platform\Link;

use Magento\Catalog\Api\Data\ProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Subscription
{
    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Product
     */
    protected $platformProductHelper;

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
     * @param \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Model\Product\Url $productUrlModel
     * @param \Magento\Catalog\Helper\Image $imageHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\Product\Url $productUrlModel,
        \Magento\Catalog\Helper\Image $imageHelper
    ) {
        $this->platformProductHelper = $platformProductHelper;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productUrlModel = $productUrlModel;
        $this->imageHelper = $imageHelper;
    }
    
    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface[] $subscriptions
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionInterface[]
     */
    public function linkSubscriptionsProduct(array $subscriptions)
    {
        $magentoProducts = $this->getMagentoProducts($subscriptions);

        foreach ($subscriptions as $subscription) {
            try {
                $platformProduct = $this->platformProductHelper->getProduct($subscription->getProductSku());
            } catch (NoSuchEntityException $e) {
                continue;
            }

            $platformProduct->setImageUrl($this->getProductImageUrl($magentoProducts[$subscription->getProductSku()]));
            $platformProduct->setUrl($this->getProductUrl($magentoProducts[$subscription->getProductSku()]));

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
}
