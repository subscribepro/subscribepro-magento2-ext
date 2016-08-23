<?php

namespace Swarming\SubscribePro\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class Product
{
    /**
     * @param ProductInterface $product
     * @return bool
     */
    public function isSubscriptionEnabled(ProductInterface $product)
    {
        $attribute = $product->getCustomAttribute(SubscriptionModifier::SUBSCRIPTION_ENABLED);
        return $attribute && $attribute->getValue();
    }
}
