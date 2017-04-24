<?php

namespace Swarming\SubscribePro\Model\CatalogRule\Inspector;

use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;
use Swarming\SubscribePro\Model\CatalogRule\Inspector\DefaultInspector;

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
        /** @var \Magento\Catalog\Model\Product\Configuration\Item\Option $customOption */
        $customOption = $product->getCustomOption('bundle_selection_ids');
        return $customOption
            ? unserialize($customOption->getValue())
            : [];
    }
}
