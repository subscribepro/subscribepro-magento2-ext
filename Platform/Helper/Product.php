<?php

namespace Swarming\SubscribePro\Platform\Helper;

use Magento\Framework\Exception\NoSuchEntityException;

class Product
{
    /**
     * @var \SubscribePro\Service\Product\ProductService
     */
    protected $sdkProductService;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param string|null $websiteCode
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform,
        $websiteCode = null
    ) {
        $this->sdkProductService = $platform->getSdk($websiteCode)->getProductService();
    }

    /**
     * @param string $sku
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProduct($sku)
    {
        $products = $this->sdkProductService->loadProducts($sku);

        if (empty($products)) {
            throw new NoSuchEntityException(__('Product is not found on Subscribe Pro platform.'));
        }

        return $products[0];
    }
    
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $magentoProduct
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveProduct($magentoProduct)
    {
        $products = $this->sdkProductService->loadProducts($magentoProduct->getSku());
        if (!empty($products)) {
            $product = $products[0];
        } else {
            $product = $this->sdkProductService->createProduct();
        }
        $product->setSku($magentoProduct->getSku())
            ->setPrice($magentoProduct->getPrice())
            ->setName($magentoProduct->getName());

        return $this->sdkProductService->saveProduct($product);
    }
}
