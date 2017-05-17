<?php

namespace Swarming\SubscribePro\Plugin\AdminOrder;

class PaymentMethods
{
    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * Plugin for the getMethods() function
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form $subject
     * @param \Closure $proceed
     * @return array
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

    /**
     * Checks to see if a subscription is present in the quote items
     *
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection|null $items
     * @return bool
     */
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

    /**
     * Filters out the payment methods other than the SubscribePro ones
     *
     * @param array $methods
     * @return array
     */
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