<?php

namespace Swarming\SubscribePro\Helper\QuoteItem;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Api\Data\ProductOptionInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;

class ProductOption
{
    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $reflectionObjectProcessor;

    /**
     * @param \Magento\Framework\Reflection\DataObjectProcessor $reflectionObjectProcessor
     */
    public function __construct(
        \Magento\Framework\Reflection\DataObjectProcessor $reflectionObjectProcessor
    ) {
        $this->reflectionObjectProcessor = $reflectionObjectProcessor;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @return array
     */
    public function getProductOptions($quoteItem)
    {
        $productOptions = $quoteItem->getProductOption();
        if ($productOptions) {
            $productOptions = $this->reflectionObjectProcessor->buildOutputDataArray($productOptions, ProductOptionInterface::class);
            $productOptions = $this->cleanSubscriptionOption($productOptions);
        } else {
            $productOptions = [];
        }
        return $productOptions;
    }

    /**
     * @param array $productOptions
     * @return array
     */
    protected function cleanSubscriptionOption($productOptions)
    {
        if (isset($productOptions[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][OptionProcessor::KEY_SUBSCRIPTION_OPTION])) {
            unset($productOptions[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][OptionProcessor::KEY_SUBSCRIPTION_OPTION]);
        }

        if (empty($productOptions[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY])) {
            unset($productOptions[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);
        }
        return $productOptions;
    }
}
