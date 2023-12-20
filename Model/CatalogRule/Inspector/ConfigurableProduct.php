<?php

namespace Swarming\SubscribePro\Model\CatalogRule\Inspector;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item\Option;
use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;

class ConfigurableProduct extends DefaultInspector implements InspectorInterface
{
    /**
     * @param Product $product
     * @return bool
     */
    public function isApplied($product)
    {
        $childProduct = $this->getChildProduct($product);
        return $childProduct
            ? $this->isAppliedToProduct($childProduct)
            : $this->isAppliedToProduct($product);
    }

    /**
     * @param Product $product
     * @return Product
     */
    protected function getChildProduct($product)
    {
        /** @var Option $customOption */
        $customOption = $product->getCustomOption('simple_product');
        return $customOption && $customOption->getProduct() ? $customOption->getProduct() : null;
    }
}
