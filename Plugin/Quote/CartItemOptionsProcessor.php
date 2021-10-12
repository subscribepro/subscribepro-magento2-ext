<?php

namespace Swarming\SubscribePro\Plugin\Quote;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor as QuoteCartItemOptionsProcessor;

class CartItemOptionsProcessor
{
    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor
     */
    protected $subscriptionOptionProcessor;

    /**
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor $subscriptionOptionProcessor
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor $subscriptionOptionProcessor
    ) {
        $this->subscriptionOptionProcessor = $subscriptionOptionProcessor;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $subject
     * @param \Closure $proceed
     * @param string $productType
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Framework\DataObject|null
     */
    public function aroundGetBuyRequest(QuoteCartItemOptionsProcessor $subject, \Closure $proceed, $productType, CartItemInterface $cartItem)
    {
        $buyRequest = $proceed($productType, $cartItem);

        $subscriptionBuyRequest = $this->subscriptionOptionProcessor->convertToBuyRequest($cartItem);

        if (!$subscriptionBuyRequest) {
            return $buyRequest;
        }

        if (!$buyRequest) {
            return $subscriptionBuyRequest;
        }

        if ($buyRequest instanceof DataObject) {
            $buyRequest->addData($subscriptionBuyRequest->getData());
        } elseif (is_numeric($buyRequest)) {
            $subscriptionBuyRequest->setData('qty', $buyRequest);
            $buyRequest = $subscriptionBuyRequest;
        }
        return $buyRequest;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Quote\Api\Data\CartItemInterface
     */
    public function aroundApplyCustomOptions(QuoteCartItemOptionsProcessor $subject, \Closure $proceed, CartItemInterface $cartItem)
    {
        $cartItem = $proceed($cartItem);
        $this->subscriptionOptionProcessor->processOptions($cartItem);
        return $cartItem;
    }
}
