<?php

namespace Swarming\SubscribePro\Platform\Service;

use Swarming\SubscribePro\Api\Data\ProductInterface;

/**
 * @method \SubscribePro\Service\Product\ProductService getService($websiteId = null)
 */
class Product extends AbstractService
{
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
