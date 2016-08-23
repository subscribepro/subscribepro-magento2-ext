<?php

namespace Swarming\SubscribePro\Platform\Service;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @method \SubscribePro\Service\Product\ProductService getService($websiteCode = null)
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
        $products = $this->getService($websiteId)->loadProducts($sku);

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
    public function saveProduct($magentoProduct, $websiteId = null)
    {
        $products = $this->getService($websiteId)->loadProducts($magentoProduct->getSku());
        if (!empty($products)) {
            $product = $products[0];
        } else {
            $product = $this->getService($websiteId)->createProduct();
        }
        $product->setSku($magentoProduct->getSku())
            ->setPrice($magentoProduct->getPrice())
            ->setName($magentoProduct->getName());

        return $this->getService($websiteId)->saveProduct($product);
    }
}
