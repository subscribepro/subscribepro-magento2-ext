<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use SubscribePro\Service\Product\ProductInterface as PlatformProductInterface;

class ItemOptionsManager
{
    const OPTION_SUBSCRIPTION_OPTION = 'subscription-option';
    const OPTION_SUBSCRIPTION_INTERVAL = 'subscription-interval';

    const SUBSCRIPTION_CREATING = 'create_subscription';
    const SUBSCRIPTION_INTERVAL = 'subscription_interval';

    /**
     * @var \Magento\Quote\Model\Quote\Item\OptionFactory
     */
    protected $itemOptionFactory;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductService;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @param \Magento\Quote\Model\Quote\Item\OptionFactory $itemOptionFactory
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     */
    public function __construct(
        \Magento\Quote\Model\Quote\Item\OptionFactory $itemOptionFactory,
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService,
        \Swarming\SubscribePro\Helper\Product $productHelper
    ) {
        $this->itemOptionFactory = $itemOptionFactory;
        $this->platformProductService = $platformProductService;
        $this->productHelper = $productHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $subscriptionInterval
     * @param string $subscriptionOption
     * @param callable $catchCallback
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addQuoteItemOptions($quoteItem, $product, $subscriptionInterval, $subscriptionOption, callable $catchCallback)
    {
        if (!$this->productHelper->isSubscriptionEnabled($product)) {
            return;
        }

        $platformProduct = $this->getPlatformProduct($product);

        $subscriptionOption = $this->getSubscriptionOption($platformProduct, $subscriptionOption);
        if ($subscriptionOption != PlatformProductInterface::SO_SUBSCRIPTION) {
            $this->addQuoteItemOption($quoteItem, self::SUBSCRIPTION_CREATING, false);
            return;
        }

        $subscriptionInterval = $this->getSubscriptionInterval($platformProduct, $subscriptionInterval);
        try {
            $this->checkQuantity($quoteItem->getQty(), $platformProduct, $product);
            $this->checkInterval($subscriptionInterval, $platformProduct);
        } catch (LocalizedException $e) {
            $catchCallback($e, $platformProduct);
            return;
        }

        $this->addQuoteItemOption($quoteItem, self::SUBSCRIPTION_INTERVAL, $subscriptionInterval);
        $this->addQuoteItemOption($quoteItem, self::SUBSCRIPTION_CREATING, true);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param string $key
     * @param string $value
     */
    public function addQuoteItemOption($quoteItem, $key, $value)
    {
        $quoteItemOption = $this->itemOptionFactory->create()
            ->setProduct($quoteItem->getProduct())
            ->setCode($key)
            ->setValue($value);
        $quoteItem->addOption($quoteItemOption);
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPlatformProduct($product)
    {
        try {
            $platformProduct = $this->platformProductService->getProduct($product->getSku());
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Product "%1" is not found on Subscribe Pro platform.', $product->getName()));
        }

        return $platformProduct;
    }

    /**
     * @param \SubscribePro\Service\Product\ProductInterface $platformProduct
     * @param string|null $subscriptionOption
     * @return string
     */
    protected function getSubscriptionOption(PlatformProductInterface $platformProduct, $subscriptionOption = null)
    {
        if ($platformProduct->getSubscriptionOptionMode() == PlatformProductInterface::SOM_SUBSCRIPTION_ONLY) {
            $subscriptionOption = PlatformProductInterface::SO_SUBSCRIPTION;
        }
        return $subscriptionOption ?: $platformProduct->getDefaultSubscriptionOption();
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param string|null $subscriptionInterval
     * @return string|null
     */
    protected function getSubscriptionInterval($platformProduct, $subscriptionInterval)
    {
        if (!$subscriptionInterval) {
            $subscriptionInterval = $platformProduct->getDefaultInterval();
        }

        if (!$subscriptionInterval && $platformProduct->getIntervals()) {
            $subscriptionInterval = $platformProduct->getIntervals()[0];
        }
        return $subscriptionInterval;
    }

    /**
     * @param int $qty
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function checkQuantity($qty, $platformProduct, $product)
    {
        if ($qty < $platformProduct->getMinQty()) {
            throw new LocalizedException(__(
                'Product "%1" requires minimum quantity of %2 for subscription.',
                $product->getName(),
                $platformProduct->getMinQty()
            ));
        }

        if ($platformProduct->getMaxQty() && $qty > $platformProduct->getMaxQty()) {
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
}
