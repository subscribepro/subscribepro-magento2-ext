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
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform
    ) {
        $this->sdkProductService = $platform->getSdk()->getProductService();
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
}
