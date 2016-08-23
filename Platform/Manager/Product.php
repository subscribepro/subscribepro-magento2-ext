<?php

namespace Swarming\SubscribePro\Platform\Manager;

use Magento\Framework\Exception\NoSuchEntityException;

class Product
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductService;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService
    ) {
        $this->platformProductService = $platformProductService;
    }
    
    /**
     * @param string $sku
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProduct($sku, $websiteId = null)
    {
        $products = $this->platformProductService->loadProducts($sku, $websiteId);

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
        $products = $this->platformProductService->loadProducts(
            $magentoProduct->getSku(),
            $websiteId
        );

        $product = !empty($products)
            ? $products[0]
            : $this->platformProductService->createProduct([], $websiteId);

        $product->setSku($magentoProduct->getSku())
            ->setPrice($magentoProduct->getPrice())
            ->setName($magentoProduct->getName());

        return $this->platformProductService->saveProduct($product, $websiteId);
    }
}
