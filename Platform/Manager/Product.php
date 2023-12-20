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
     * @var \Swarming\SubscribePro\Platform\Storage\Product
     */
    protected $platformProductStorage;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     * @param \Swarming\SubscribePro\Platform\Storage\Product $productStorage
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService,
        \Swarming\SubscribePro\Platform\Storage\Product $productStorage
    ) {
        $this->platformProductService = $platformProductService;
        $this->platformProductStorage = $productStorage;
    }

    /**
     * @param string $sku
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProduct($sku, $websiteId = null)
    {
        $platformProduct = $this->platformProductStorage->load($sku, $websiteId);
        if (!$platformProduct) {
            $platformProduct = $this->retrieveProduct($sku, $websiteId);
            $this->platformProductStorage->save($platformProduct, $websiteId);
        }
        /* @phpstan-ignore-next-line */
        return $platformProduct;
    }

    /**
     * @param string $sku
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Product\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function retrieveProduct($sku, $websiteId = null)
    {
        $platformProducts = $this->platformProductService->loadProducts($sku, $websiteId);
        if (empty($platformProducts)) {
            throw new NoSuchEntityException(__('Product is not found on Subscribe Pro platform.'));
        }
        return $platformProducts[0];
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int|null $websiteId
     * @return \SubscribePro\Service\Product\ProductInterface
     * @throws \SubscribePro\Exception\InvalidArgumentException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveProduct($product, $websiteId = null)
    {
        $platformProduct = $this->retrieveOrCreateNewProduct($product, $websiteId);
        $platformProduct->setSku($product->getSku())
            ->setPrice($product->getPrice())
            ->setName($product->getName());

        $platformProduct = $this->platformProductService->saveProduct($platformProduct, $websiteId);

        $this->platformProductStorage->save($platformProduct, $websiteId);

        return $platformProduct;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string|int|null $websiteId
     * @return \SubscribePro\Service\Product\ProductInterface
     */
    protected function retrieveOrCreateNewProduct($product, $websiteId = null)
    {
        try {
            $platformProduct = $this->platformProductStorage->load($product->getSku(), $websiteId)
                ?: $this->retrieveProduct($product->getSku(), $websiteId);
            $this->platformProductStorage->remove($product->getSku(), $websiteId);
        } catch (NoSuchEntityException $e) {
            $platformProduct = $this->platformProductService->createProduct([], $websiteId);
        }
        return $platformProduct;
    }
}
