<?php

namespace Swarming\SubscribePro\Platform\Service;

use Swarming\SubscribePro\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @method \SubscribePro\Service\Product\ProductService getService($websiteId = null)
 */
class Product extends AbstractService
{
    /**
     * @param string $sku
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProduct($sku, $websiteId = null)
    {
        $products = $this->loadProducts($sku, $websiteId);

        if (empty($products)) {
            throw new NoSuchEntityException(__('Product is not found on Subscribe Pro platform.'));
        }

        return $products[0];
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $magentoProduct
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveMagentoProduct($magentoProduct, $websiteId = null)
    {
        $products = $this->loadProducts($magentoProduct->getSku(), $websiteId);

        $product = !empty($products) ? $products[0] : $this->createProduct([], $websiteId);

        $product->setSku($magentoProduct->getSku())
            ->setPrice($magentoProduct->getPrice())
            ->setName($magentoProduct->getName());

        return $this->saveProduct($product, $websiteId);
    }

    /**
     * @param array $productData
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     */
    public function createProduct(array $productData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createProduct($productData);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $product
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveProduct(ProductInterface $product, $websiteId = null)
    {
        return $this->getService($websiteId)->saveProduct($product);
    }

    /**
     * @param int $productId
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProduct($productId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadProduct($productId);
    }

    /**
     * @param string|null $sku
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface[]
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProducts($sku = null, $websiteId = null)
    {
        return $this->getService($websiteId)->loadProducts($sku);
    }
}
