<?php

namespace Swarming\SubscribePro\Model\CatalogRule\Inspector;

use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;
use Swarming\SubscribePro\Model\CatalogRule\Inspector\DefaultInspector;

class ConfigurableProduct extends DefaultInspector implements InspectorInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
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
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Product
     */
    protected function getChildProduct($product)
    {
        $customOption = $product->getCustomOption('simple_product');
        return $customOption && $customOption->getProduct() ? $customOption->getProduct() : null;
    }
}
