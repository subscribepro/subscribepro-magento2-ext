<?php

namespace Swarming\SubscribePro\Model\CatalogRule;

interface InspectorInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isApplied($product);
}
