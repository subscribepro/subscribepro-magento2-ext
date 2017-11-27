<?php

namespace Swarming\SubscribePro\Model\Quote\SubscriptionOption;

use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item\CartItemProcessorInterface;

class OptionProcessor implements CartItemProcessorInterface
{
    const KEY_SUBSCRIPTION_OPTION = 'subscription_option';

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Quote\Model\Quote\ProductOptionFactory
     */
    protected $productOptionFactory;

    /**
     * @var \Magento\Quote\Api\Data\ProductOptionExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var \Swarming\SubscribePro\Api\Data\SubscriptionOptionInterfaceFactory
     */
    protected $subscriptionOptionFactory;

    /**
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Quote\Model\Quote\ProductOptionFactory $productOptionFactory
     * @param \Magento\Quote\Api\Data\ProductOptionExtensionFactory $extensionFactory
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionOptionInterfaceFactory $subscriptionOptionFactory
     */
    public function __construct(
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Quote\Model\Quote\ProductOptionFactory $productOptionFactory,
        \Magento\Quote\Api\Data\ProductOptionExtensionFactory $extensionFactory,
        \Swarming\SubscribePro\Api\Data\SubscriptionOptionInterfaceFactory $subscriptionOptionFactory
    ) {
        $this->objectFactory = $objectFactory;
        $this->productOptionFactory = $productOptionFactory;
        $this->extensionFactory = $extensionFactory;
        $this->subscriptionOptionFactory = $subscriptionOptionFactory;
    }

    /**
     * Convert cart item to buy request object
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Framework\DataObject|null
     */
    public function convertToBuyRequest(CartItemInterface $cartItem)
    {
        if ($cartItem->getProductOption()
            && $cartItem->getProductOption()->getExtensionAttributes()
            && $cartItem->getProductOption()->getExtensionAttributes()->getSubscriptionOption()
        ) {
            $subscriptionOption = $cartItem->getProductOption()->getExtensionAttributes()->getSubscriptionOption();
            $subscriptionOption = $subscriptionOption->__toArray();
            if (!empty($subscriptionOption) && is_array($subscriptionOption)) {
                return $this->objectFactory->create([self::KEY_SUBSCRIPTION_OPTION => $subscriptionOption]);
            }
        }
        return null;
    }

    /**
     * Process cart item subscription option
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Quote\Api\Data\CartItemInterface
     */
    public function processOptions(CartItemInterface $cartItem)
    {
        $options = $this->getOptions($cartItem);
        if (!empty($options)) {
            $subscriptionOption = $this->subscriptionOptionFactory->create(['data' => $options]);

            $productOption = $cartItem->getProductOption() ?: $this->productOptionFactory->create();
            $extensibleAttribute = $productOption->getExtensionAttributes() ?: $this->extensionFactory->create();

            $extensibleAttribute->setSubscriptionOption($subscriptionOption);
            $productOption->setExtensionAttributes($extensibleAttribute);
            $cartItem->setProductOption($productOption);
        }
        return $cartItem;
    }

    /**
     * Receive custom option from buy request
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return array
     */
    protected function getOptions(CartItemInterface $cartItem)
    {
        $buyRequest = !empty($cartItem->getOptionByCode('info_buyRequest'))
            ? json_decode($cartItem->getOptionByCode('info_buyRequest')->getValue(), true)
            : null;

        if (!is_array($buyRequest)
            || !isset($buyRequest[self::KEY_SUBSCRIPTION_OPTION])
            || !is_array($buyRequest[self::KEY_SUBSCRIPTION_OPTION])
        ) {
            return [];
        }

        return $buyRequest[self::KEY_SUBSCRIPTION_OPTION];
    }
}
