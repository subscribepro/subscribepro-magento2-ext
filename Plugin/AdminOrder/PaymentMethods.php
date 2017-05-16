<?php

namespace Swarming\SubscribePro\Plugin\AdminOrder;

class PaymentMethods
{
    /**
     * @param \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
    ) {
        $this->orderItemHelper = $orderItemHelper;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form $subject
     * @param \Closure $proceed
     */
    public function aroundGetMethods(
        \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form $subject,
        \Closure $proceed
    ) {
        $methods = $proceed();
        return $methods;
    }
}
