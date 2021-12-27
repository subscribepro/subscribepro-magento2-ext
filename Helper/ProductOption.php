<?php

namespace Swarming\SubscribePro\Helper;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Api\Data\ProductOptionInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;

class ProductOption
{
    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $reflectionObjectProcessor;

    /**
     * @var \Magento\Framework\Webapi\ServiceInputProcessor
     */
    protected $inputProcessor;
    
    /**
     * @var \Magento\ConfigurableProduct\Model\Quote\Item\CartItemProcessor
     */
    protected $configurableProductCartItemProcessor;

    /**
     * @var \Magento\Bundle\Model\CartItemProcessor
     */
    protected $bundleProductCartItemProcessor;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Catalog\Mode\CustomOptions\CustomOptionProcessor
     */
    protected $customOptionProcessor;

    /**
     * @param \Magento\Framework\Reflection\DataObjectProcessor $reflectionObjectProcessor
     * @param \Magento\Framework\Webapi\ServiceInputProcessor $inputProcessor
     * @param \Magento\ConfigurableProduct\Model\Quote\Item\CartItemProcessor $configurableProductCartItemProcessor
     * @param \Magento\Bundle\Model\CartItemProcessor $bundleProductCartItemProcessor
     * @param \Magento\Catalog\Model\CustomOptions\CustomOptionProcessor $customOptionProcessor
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\Reflection\DataObjectProcessor $reflectionObjectProcessor,
        \Magento\Framework\Webapi\ServiceInputProcessor $inputProcessor,
        \Magento\ConfigurableProduct\Model\Quote\Item\CartItemProcessor $configurableProductCartItemProcessor,
        \Magento\Bundle\Model\CartItemProcessor $bundleProductCartItemProcessor,
        \Magento\Catalog\Model\CustomOptions\CustomOptionProcessor $customOptionProcessor,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->reflectionObjectProcessor = $reflectionObjectProcessor;
        $this->inputProcessor = $inputProcessor;
        $this->configurableProductCartItemProcessor = $configurableProductCartItemProcessor;
        $this->bundleProductCartItemProcessor = $bundleProductCartItemProcessor;
        $this->customOptionProcessor = $customOptionProcessor;
        $this->logger = $logger;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\SubscriptionInterface $subscription
     * @return \Magento\Quote\Api\Data\CartItemInterface
     */
    public function getCartItem($subscription)
    {
        $cartItemData = [
            CartItemInterface::KEY_SKU => $subscription->getProductSku(),
            CartItemInterface::KEY_QTY => $subscription->getQty(),
            CartItemInterface::KEY_PRODUCT_OPTION => $subscription->getProductOption()
        ];

        /** @var \Magento\Quote\Api\Data\CartItemInterface $cartItem */
        $cartItem = $this->inputProcessor->convertValue($cartItemData, CartItemInterface::class);
        return $cartItem;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @return array
     */
    public function getProductOption(CartItemInterface $quoteItem)
    {
        $this->processProductOptions($quoteItem);
        $productOptions = $quoteItem->getProductOption();
        if ($productOptions) {
            $productOptions = $this->reflectionObjectProcessor->buildOutputDataArray(
                $productOptions,
                ProductOptionInterface::class
            );
            $productOptions = $this->cleanSubscriptionOption($productOptions);
        } else {
            $productOptions = [];
        }
        return $productOptions;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @return null
     */
    protected function processProductOptions($quoteItem)
    {
        switch ($quoteItem->getProductType()) {
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $this->configurableProductCartItemProcessor->processOptions($quoteItem);
                break;
            
            case \Magento\Bundle\Model\Product\Type::TYPE_CODE:
                $this->bundleProductCartItemProcessor->processOptions($quoteItem);
                break;

            default:
                break;
        }

        // Process custom options for any product type
        $this->customOptionProcessor->processOptions($quoteItem);
    }

    /**
     * @param array $productOptions
     * @return array
     */
    protected function cleanSubscriptionOption($productOptions)
    {
        if (isset(
            $productOptions[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][OptionProcessor::KEY_SUBSCRIPTION_OPTION]
        )) {
            unset(
                $productOptions[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]
                [OptionProcessor::KEY_SUBSCRIPTION_OPTION]
            );
        }

        if (empty($productOptions[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY])) {
            unset($productOptions[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);
        }
        return $productOptions;
    }
}
