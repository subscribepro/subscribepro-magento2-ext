<?php

namespace Swarming\SubscribePro\Plugin\ShipperHQ;

use ShipperHQ\Shipper\Model\Carrier\Processor\ShipperMapper;
use Magento\Quote\Model\Quote\Item;

class ShipperMapperPlugin
{
    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Model\Config\ShipperHQ
     */
    protected $shipperHQConfig;

    /**
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Model\Config\ShipperHQ $shipperHQConfig
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Model\Config\ShipperHQ $shipperHQConfig
    ) {
        $this->shipperHQConfig = $shipperHQConfig;
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * @param ShipperMapper $mapper
     * @param callable $proceed
     * @param array $reqdAttributeNames
     * @param Item $item
     * @return array
     */
    public function aroundPopulateAttributes(
        \ShipperHQ\Shipper\Model\Carrier\Processor\ShipperMapper $mapper, /* @phpstan-ignore-line */
        callable                                                 $proceed,
        array                                                    $reqdAttributeNames,
        Item $item
    ) {
        $attributes = $proceed($reqdAttributeNames, $item);

        if ($recurringShippingCode = $this->determineSubscriptionCode($item)) {
            $shippingGroup = '';
            $shippingKey = '';
            $shippingGroupSet = false;

            if (is_array($attributes)) {
                foreach ($attributes as $key => $attribute) {
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
     * @return string|bool
     */
    private function determineSubscriptionCode($item)
    {
        if ($this->quoteItemHelper->isItemFulfilsSubscription($item)) {
            return $this->shipperHQConfig->getRecurringOrderGroup();
        }
        if ($this->quoteItemHelper->hasSubscription($item)) {
            return $this->shipperHQConfig->getSubscriptionProductGroup();
        }
        return false;
    }
}
