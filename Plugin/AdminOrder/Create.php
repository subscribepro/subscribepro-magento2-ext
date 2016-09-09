<?php

namespace Swarming\SubscribePro\Plugin\AdminOrder;

class Create
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
     * @param \Magento\Sales\Model\AdminOrder\Create $subject
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param true|null $qtyFlag
     * @return array
     */
    public function beforeInitFromOrderItem(
        \Magento\Sales\Model\AdminOrder\Create $subject,
        \Magento\Sales\Model\Order\Item $orderItem,
        $qtyFlag = null
    ) {
        $this->orderItemHelper->cleanSubscriptionParams($orderItem, true);
        $this->orderItemHelper->cleanAdditionalOptions($orderItem);
        return [$orderItem, $qtyFlag];
    }
}
