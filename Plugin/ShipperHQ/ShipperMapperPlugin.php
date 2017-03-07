<?php

namespace Swarming\SubscribePro\Plugin\ShipperHQ;

use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;

class ShipperMapperPlugin
{
    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    public function __construct(\Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper) {
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * @param \ShipperHQ\Shipper\Model\Carrier\Processor\ShipperMapper $mapper
     * @param Closure $proceed
     * @param $reqdAttributeNames
     * @param \Swarming\SubscribePro\Helper\QuoteItem $item
     * @return array
     */
    public function aroundPopulateAttributes(\ShipperHQ\Shipper\Model\Carrier\Processor\ShipperMapper $mapper, callable $proceed, $reqdAttributeNames, $item)
    {
        $attributes = $proceed($reqdAttributeNames, $item);

        if ($this->determineIfSubscription($item)) {
            $shippingGroup = '';
            $shippingGroupSet = false;

            $recurringShippingCode = 'SUBSCRIBEPRO_RECURRING';

            if (is_array($attributes)) {
                foreach($attributes as $key => $attribute) {
                    if (isset($attribute['name']) && $attribute['name'] === 'shipperhq_shipping_group') {
                        $shippingGroup = $attribute['value'] . '#' . $recurringShippingCode;
                        $shippingGroupSet = true;
                        $shippingKey = $key;
                    }
                }
                if (!$shippingGroupSet) {
                    $attributes[] = ['name' => 'shipperhq_shipping_group', 'value' => $recurringShippingCode];
                } else {
                    $attributes[$shippingKey] = ['name' => 'shipperhq_shipping_group', 'value' => $shippingGroup];
                }
            }
        }
        return $attributes;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    private function determineIfSubscription($item)
    {
        return $this->quoteItemHelper->isFulfilsSubscription($item) || $this->quoteItemHelper->hasSubscription($item);
    }
}

