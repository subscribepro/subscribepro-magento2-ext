<?php

namespace Swarming\SubscribePro\Plugin\Checkout;

class Cart
{
    /**
     * @var \Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelper;

    /**
     * @param \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
    ) {
        $this->orderItemHelper = $orderItemHelper;
    }

    /**
     * @param \Magento\Checkout\Model\Cart $subject
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param true|null $qtyFlag
     * @return array
     */
    public function beforeAddOrderItem(
        \Magento\Checkout\Model\Cart $subject,
        \Magento\Sales\Model\Order\Item $orderItem,
        $qtyFlag = null
    ) {
        $this->orderItemHelper->cleanSubscriptionParams($orderItem);
        $this->orderItemHelper->cleanAdditionalOptions($orderItem);
        return [$orderItem, $qtyFlag];
    }
}
