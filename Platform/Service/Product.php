<?php

namespace Swarming\SubscribePro\Platform\Service;

use Swarming\SubscribePro\Api\Data\ProductInterface;

/**
 * @method \SubscribePro\Service\Product\ProductService getService($websiteId = null)
 */
class Product extends AbstractService
{
    /**
     * @param array $platformProductData
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     */
    public function createProduct(array $platformProductData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createProduct($platformProductData);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveProduct(ProductInterface $platformProduct, $websiteId = null)
    {
        return $this->getService($websiteId)->saveProduct($platformProduct);
    }

    /**
     * @param int $platformProductId
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProduct($platformProductId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadProduct($platformProductId);
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
