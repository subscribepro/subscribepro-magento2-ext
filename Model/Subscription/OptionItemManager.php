<?php

namespace Swarming\SubscribePro\Model\Subscription;

use Magento\Catalog\Model\Product\Type\AbstractType as ProductAbstractType;
use Magento\Framework\Exception\NoSuchEntityException;

class OptionItemManager
{
    /**
     * @var \Swarming\SubscribePro\Model\Subscription\OptionItemFactory
     */
    protected $subscriptionItemFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Swarming\SubscribePro\Helper\ProductOption
     */
    protected $productOptionHelper;

    /**
     * @var \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor
     */
    protected $cartItemOptionProcessor;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @param \Swarming\SubscribePro\Model\Subscription\OptionItemFactory $subscriptionItemFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Swarming\SubscribePro\Helper\ProductOption $productOptionHelper
     * @param \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Subscription\OptionItemFactory $subscriptionItemFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Swarming\SubscribePro\Helper\ProductOption $productOptionHelper,
        \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor,
        \Magento\Framework\DataObject\Factory $objectFactory
    ) {
        $this->subscriptionItemFactory = $subscriptionItemFactory;
        $this->productRepository = $productRepository;
        $this->productOptionHelper = $productOptionHelper;
        $this->cartItemOptionProcessor = $cartItemOptionProcessor;
        $this->objectFactory = $objectFactory;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface $subscription
     * @return \Swarming\SubscribePro\Model\Subscription\OptionItem
     */
    public function getSubscriptionOptionItem($subscription)
    {
        /** @var \Swarming\SubscribePro\Model\Subscription\OptionItem $subscriptionItem */
        $subscriptionItem = $this->subscriptionItemFactory->create();

        try {
            $product = $this->productRepository->get($subscription->getProductSku());
        } catch (NoSuchEntityException $e) {
            return $subscriptionItem;
        }

        $subscriptionItem->setProduct($product);

        $cartItem = $this->productOptionHelper->getCartItem($subscription);
        $subscriptionItem->setOptions($this->getOptions($product, $cartItem));

        return $subscriptionItem;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return array
     */
    protected function getOptions($product, $cartItem)
    {
        $product->unsetData('_cache_instance_options_collection'); /* TODO Temporary magento bug fix */

        $buyRequest = $this->cartItemOptionProcessor->getBuyRequest($product->getTypeId(), $cartItem);
        $buyRequest = $this->processBuyRequestObject($buyRequest);
        $product->getTypeInstance()->processConfiguration(
            $buyRequest,
            $product,
            ProductAbstractType::PROCESS_MODE_FULL
        );
        return $product->getCustomOptions();
    }

    /**
     * @param \Magento\Framework\DataObject|array|int $buyRequest
     * @return \Magento\Framework\DataObject
     */
    protected function processBuyRequestObject($buyRequest)
    {
        $buyRequest = is_array($buyRequest) || is_object($buyRequest) ? $buyRequest : ['qty' => $buyRequest];
        $buyRequest = is_object($buyRequest) ? $buyRequest : $this->objectFactory->create($buyRequest);
        return $buyRequest;
    }
}
