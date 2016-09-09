<?php

namespace Swarming\SubscribePro\Plugin\GroupedProduct;

use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class TypeGrouped
{
    /**
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection
     */
    public function afterGetAssociatedProductCollection(
        \Magento\GroupedProduct\Model\Product\Type\Grouped $subject,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection $collection
    ) {
        $collection->addAttributeToSelect(SubscriptionModifier::SUBSCRIPTION_ENABLED);
        return $collection;
    }
}
