<?php

namespace Swarming\SubscribePro\Model\CatalogRule\Inspector;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item\Option;
use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;

class BundleProduct extends DefaultInspector implements InspectorInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isApplied($product)
    {
        return $this->isAppliedToProduct($product) || $this->isAppliedToChildProducts($product);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    protected function isAppliedToChildProducts($product)
    {
        $isApplied = false;
        $selectionIds = $this->getSelectionsIds($product);
        foreach ($selectionIds as $selectionId) {
            /** @var Option $selection */
            $selection = $product->getCustomOption('selection_qty_' . $selectionId);
            if ($selection && $selection->getProduct()) {
                $isApplied = $this->isAppliedToProduct($selection->getProduct());
            }
            if ($isApplied) {
                break;
            }
        }
        return $isApplied;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string[]
     */
    protected function getSelectionsIds($product)
    {
        /** @var Option $customOption */
        $customOption = $product->getCustomOption('bundle_selection_ids');
        return $customOption
            ? json_decode($customOption->getValue())
            : [];
    }
}
