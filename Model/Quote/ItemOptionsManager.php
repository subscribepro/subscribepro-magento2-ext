<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Exception\LocalizedException;
use SubscribePro\Service\Product\ProductInterface as PlatformProductInterface;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class ItemOptionsManager
{
    const OPTION_SUBSCRIPTION_DELIVERY = 'subscription-delivery-option';
    const OPTION_SUBSCRIPTION_INTERVAL = 'subscription-interval';

    const SUBSCRIPTION_CREATING = 'create_subscription';
    const SUBSCRIPTION_INTERVAL = 'subscription_interval';

    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Product
     */
    protected $platformProductHelper;

    /**
     * @var \Magento\Quote\Model\Quote\Item\OptionFactory
     */
    protected $itemOptionFactory;

    /**
     * @param \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper
     * @param \Magento\Quote\Model\Quote\Item\OptionFactory $itemOptionFactory
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper,
        \Magento\Quote\Model\Quote\Item\OptionFactory $itemOptionFactory
    ) {
        $this->platformProductHelper = $platformProductHelper;
        $this->itemOptionFactory = $itemOptionFactory;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    protected function isProductSubscriptionEnabled(ProductInterface $product)
    {
        $attribute = $product->getCustomAttribute(SubscriptionModifier::SUBSCRIPTION_ENABLED);
        return $attribute && $attribute->getValue();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Magento\Catalog\Model\Product $product
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveProductOptions(QuoteItem $quoteItem, Product $product)
    {
        if (!$this->isProductSubscriptionEnabled($product)) {
            return;
        }

        $productRequestParams = $this->getRequestParamsByProduct($product);
        $subscriptionDelivery = $this->getParamData($productRequestParams, self::OPTION_SUBSCRIPTION_DELIVERY);
        if ($subscriptionDelivery != PlatformProductInterface::SO_SUBSCRIPTION) {
            return;
        }

        $platformProduct = $this->platformProductHelper->getProduct($product->getSku());

        $subscriptionInterval = $this->getSubscriptionInterval($productRequestParams, $platformProduct);

        $this->checkQuantity($quoteItem, $platformProduct, $product);
        $this->checkInterval($subscriptionInterval, $platformProduct);

        $this->addQuoteItemOption($quoteItem, self::SUBSCRIPTION_INTERVAL, $subscriptionInterval);
        $this->addQuoteItemOption($quoteItem, self::SUBSCRIPTION_CREATING, true);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    protected function getRequestParamsByProduct($product)
    {
        $buyRequest = $product->getCustomOption('info_buyRequest');
        return $buyRequest ? unserialize($buyRequest->getValue()) : [];
    }

    /**
     * @param array $params
     * @param string $key
     * @return string|null
     */
    protected function getParamData(array $params, $key)
    {
        return isset($params[$key]) ? $params[$key] : null;
    }

    /**
     * @param array $productRequestParams
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @return string|null
     */
    protected function getSubscriptionInterval(array $productRequestParams, $platformProduct)
    {
        $subscriptionInterval = $this->getParamData($productRequestParams, self::OPTION_SUBSCRIPTION_INTERVAL);

        if (!$subscriptionInterval) {
            $subscriptionInterval = $platformProduct->getDefaultInterval();
        }
        
        if (!$subscriptionInterval && $platformProduct->getIntervals()) {
            $subscriptionInterval = $platformProduct->getIntervals()[0];
        }
        return $subscriptionInterval;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param \Magento\Catalog\Model\Product $product
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkQuantity($quoteItem, $platformProduct, $product)
    {
        if ($quoteItem->getQty() < $platformProduct->getMinQty()) {
            throw new LocalizedException(__(
                'Product "%1" requires minimum quantity of %2 for subscription.',
                $product->getName(),
                $platformProduct->getMinQty()
            ));
        }

        if ($platformProduct->getMaxQty() && $quoteItem->getQty() > $platformProduct->getMaxQty()) {
            throw new LocalizedException(__(
                'Product "%1" allows maximum quantity of %2 for subscription.',
                $product->getName(),
                $platformProduct->getMaxQty()
            ));
        }
    }

    /**
     * @param string $subscriptionInterval
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkInterval($subscriptionInterval, $platformProduct)
    {
        if (!in_array($subscriptionInterval, $platformProduct->getIntervals())) {
            throw new LocalizedException( __('Subscription interval is not valid.'));
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param string $key
     * @param string $value
     */
    protected function addQuoteItemOption($quoteItem, $key, $value)
    {
        $quoteItemOption = $this->itemOptionFactory->create()
            ->setProduct($quoteItem->getProduct())
            ->setCode($key)
            ->setValue($value);
        $quoteItem->addOption($quoteItemOption);
    }
}
