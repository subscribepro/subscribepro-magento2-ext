<?php

namespace Swarming\SubscribePro\Plugin\AdminOrder;

class PaymentMethods
{
    /**
     * @var Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
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
        if ($this->subscriptionIsPresent($subject->getQuote()->getItemsCollection())) {
            return $this->filterOutMethods($methods);
        }

        return $methods;
    }

    protected function subscriptionIsPresent($items)
    {
        if ($items == null) {
            return false;
        }
        foreach($items as $item) {
            if ($this->quoteItemHelper->hasSubscription($item)) {
                return true;
            }
        }
        return false;
    }

    protected function filterOutMethods(array $methods)
    {
        $subscribeProMethods = [];
        foreach($methods as $method) {
            if ($method->getCode() == 'subscribe_pro' || $method->getCode() == 'subscribe_pro_vault') {
                $subscribeProMethods[] = $method;
            }
        }
        return $subscribeProMethods;
    }
}