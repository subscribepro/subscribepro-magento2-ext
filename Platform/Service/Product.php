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
     * @param $websiteId
     * @return \SubscribePro\Service\Product\ProductInterface
     */
    public function createProduct(array $platformProductData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createProduct($platformProductData);
    }

    /**
     * @param ProductInterface $platformProduct
     * @param $websiteId
     * @return \SubscribePro\Service\Product\ProductInterface
     */
    public function saveProduct(ProductInterface $platformProduct, $websiteId = null)
    {
        return $this->getService($websiteId)->saveProduct($platformProduct);
    }

    /**
     * @param $platformProductId
     * @param $websiteId
     * @return \SubscribePro\Service\Product\ProductInterface
     */
    public function loadProduct($platformProductId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadProduct($platformProductId);
    }

    /**
     * @param $sku
     * @param $websiteId
     * @return \SubscribePro\Service\Product\ProductInterface[]
     */
    public function loadProducts($sku = null, $websiteId = null)
    {
        return $this->getService($websiteId)->loadProducts($sku);
    }
}
