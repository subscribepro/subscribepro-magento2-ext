<?php

namespace Swarming\SubscribePro\Helper;

use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;

class OrderItem
{
    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
    ) {
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param int $quoteItemId
     * @param string|int $subscriptionId
     */
    public function updateOrderItem($order, $quoteItemId, $subscriptionId)
    {
        $orderItem = $order->getItemByQuoteItemId($quoteItemId);
        $this->updateAdditionalOptions($orderItem, $subscriptionId);
        $this->orderItemRepository->save($orderItem);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param string|int|null $subscriptionId
     */
    public function updateAdditionalOptions($orderItem, $subscriptionId = null)
    {
        $createNewSubscriptionAtCheckout = $this->getSubscriptionParam($orderItem, SubscriptionOptionInterface::CREATE_NEW_SUBSCRIPTION_AT_CHECKOUT);
        $itemFulfilsSubscription = $this->getSubscriptionParam($orderItem, SubscriptionOptionInterface::ITEM_FULFILS_SUBSCRIPTION);
        $subscriptionId = $subscriptionId ?: $this->getSubscriptionParam($orderItem, SubscriptionOptionInterface::SUBSCRIPTION_ID);

        // getSubscriptionParam() returns null if the parameter isn't set, which would be the case for all of these for non-subscription product
        // Whereas a subscription product that isn't selected for subscription would contain a false value for subscription-related parameters
        // And a subscription product that is selected for subscription would contain non-false values for subscription-related parameters
        if ($createNewSubscriptionAtCheckout === null
            && $itemFulfilsSubscription === null
            && $subscriptionId === null
        ) {
            return;
        }

        $additionalOptions = $this->getAdditionalOptions($orderItem);
        if (!$createNewSubscriptionAtCheckout && !$itemFulfilsSubscription) {
            $additionalOptions[] = [
                'label' => (string)__('Delivery'),
                'value' => (string)__('One Time')
            ];
        } else if ($subscriptionId) {
            $additionalOptions[] = [
                'label' => (string)__('Regular Delivery'),
                'value' => (string)__($this->getSubscriptionParam($orderItem, SubscriptionOptionInterface::INTERVAL))
            ];
            $additionalOptions[] = [
                'label' => (string)__('Subscription Id'),
                'value' => $subscriptionId,
            ];
        }

        $this->setAdditionalOptions($orderItem, $additionalOptions);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param bool $deleteAll
     */
    public function cleanSubscriptionParams($orderItem, $deleteAll = false)
    {
        $subscriptionParams = $this->getSubscriptionParams($orderItem);
        if ($deleteAll) {
            $this->setSubscriptionParams($orderItem, []);
        } elseif (!empty($subscriptionParams)) {
            unset($subscriptionParams[SubscriptionOptionInterface::IS_FULFILLING]);
            unset($subscriptionParams[SubscriptionOptionInterface::OPTION]);
            $this->setSubscriptionParams($orderItem, $subscriptionParams);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     */
    public function cleanAdditionalOptions($orderItem)
    {
        $additionalOptions = $this->getAdditionalOptions($orderItem);
        $additionalOptions = array_filter($additionalOptions, function ($option) {
            return !in_array($option['label'], [(string)__('Delivery'), (string)__('Regular Delivery'), (string)__('Subscription Id')]);
        });
        sort($additionalOptions);
        $this->setAdditionalOptions($orderItem, $additionalOptions);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return array
     */
    protected function getAdditionalOptions($orderItem)
    {
        $productOptions = $orderItem->getProductOptions();
        return isset($productOptions['additional_options']) ? $productOptions['additional_options'] : [];
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param array $additionalOptions
     * @return array
     */
    protected function setAdditionalOptions($orderItem, $additionalOptions)
    {
        $productOptions = $orderItem->getProductOptions();
        $productOptions['additional_options'] = $additionalOptions;
        $orderItem->setProductOptions($productOptions);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param string $paramKey
     * @return mixed|null
     */
    protected function getSubscriptionParam($orderItem, $paramKey)
    {
        $subscriptionParams = $this->getSubscriptionParams($orderItem);
        return isset($subscriptionParams[$paramKey]) ? $subscriptionParams[$paramKey] : null;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return array
     */
    protected function getSubscriptionParams($orderItem)
    {
        $buyRequest = $orderItem->getProductOptionByCode('info_buyRequest');
        return isset($buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION]) ? $buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION] : [];
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param array $subscriptionParams
     */
    protected function setSubscriptionParams($orderItem, $subscriptionParams)
    {
        $productOptions = $orderItem->getProductOptions();
        if (empty($subscriptionParams)) {
            unset($productOptions['info_buyRequest'][OptionProcessor::KEY_SUBSCRIPTION_OPTION]);
        } else {
            $productOptions['info_buyRequest'][OptionProcessor::KEY_SUBSCRIPTION_OPTION] = $subscriptionParams;
        }
        $orderItem->setProductOptions($productOptions);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function hasSubscription($item)
    {
        return $this->hasRecurringItem($item) || $this->hasNewSubscriptionItem($item);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function hasRecurringItem($item)
    {
        return $this->getSubscriptionParam($item, SubscriptionOptionInterface::ITEM_FULFILS_SUBSCRIPTION);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return bool
     */
    public function hasNewSubscriptionItem($item)
    {
        return $this->getSubscriptionParam($item, SubscriptionOptionInterface::CREATE_NEW_SUBSCRIPTION_AT_CHECKOUT);
    }
}
