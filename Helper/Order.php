<?php

namespace Swarming\SubscribePro\Helper;

use \Magento\Sales\Api\Data\OrderInterface;

class Order
{
    /**
     * @var OrderItem
     */
    protected $orderItemHelper;

    /**
     * @param OrderItem $orderItemHelper
     */
    public function __construct(
        OrderItem $orderItemHelper
    ) {
        $this->orderItemHelper = $orderItemHelper;
    }

    /**
     * @param OrderInterface $quote
     * @return bool
     */
    public function hasSubscription($order)
    {
        return !empty($this->getSubscriptionItems($order));
    }

    /**
     * @param OrderInterface $quote
     * @return array
     */
    public function getSubscriptionItems($order)
    {
        $subscriptions = [];

        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            if ($this->orderItemHelper->hasSubscription($item)) {
                $subscriptions[] = $item;
            }
        }

        return $subscriptions;
    }

    /**
     * Whether or not the order contains a new subscription signup
     * Depends on the create_new_subscription_at_checkout attribute on the line item.
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isNewSubscriptionOrder($order)
    {
        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            if ($this->orderItemHelper->hasNewSubscriptionItem($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether or not the quote has been built by Subscribe Pro's recurring order placement system
     * Depends on the item_added_by_subscribe_pro attribute on the line item.
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isRecurringOrder($order)
    {
        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            if ($this->orderItemHelper->hasRecurringItem($item)) {
                return true;
            }
        }

        return false;
    }
}
