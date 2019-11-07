<?php

namespace Swarming\SubscribePro\Plugin\Quote;

class ToOrderItem
{
    /**
     * @var \Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelper;

    /**
     * @param \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
     */
    public function __construct(\Swarming\SubscribePro\Helper\OrderItem $orderItemHelper)
    {
        $this->orderItemHelper = $orderItemHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array $data
     * @return \Magento\Sales\Model\Order\Item
     */
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $data = []
    ) {
        /** @var $orderItem \Magento\Sales\Model\Order\Item */
        $orderItem = $proceed($item, $data);

        $this->orderItemHelper->updateAdditionalOptions($orderItem);
        $this->orderItemHelper->cleanSubscriptionParams($orderItem);
        return $orderItem;
    }
}
