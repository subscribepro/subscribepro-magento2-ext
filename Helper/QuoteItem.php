<?php

namespace Swarming\SubscribePro\Helper;

use Swarming\SubscribePro\Model\Quote\ItemOptionsManager;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class QuoteItem
{
    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    public function isSubscriptionEnabled(AbstractItem $item)
    {
        $createSubscriptionOption = $item->getOptionByCode(ItemOptionsManager::SUBSCRIPTION_CREATING);
        return $createSubscriptionOption && $createSubscriptionOption->getValue();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool|string
     */
    public function getSubscriptionInterval(AbstractItem $item)
    {
        $intervalSubscriptionOption = $item->getOptionByCode(ItemOptionsManager::SUBSCRIPTION_INTERVAL);
        return $intervalSubscriptionOption ? $intervalSubscriptionOption->getValue() : false;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct(AbstractItem $item)
    {
        $product = $item->getProduct();
        if ($item->getProduct()->getTypeId() == Configurable::TYPE_CODE && $option = $item->getOptionByCode('simple_product')) {
            $product = $option->getProduct();
        }
        return $product;
    }
}
