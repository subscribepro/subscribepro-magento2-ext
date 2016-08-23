<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Exception\LocalizedException;
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
     * @param \Magento\Quote\Model\Quote\Item\OptionFactory $itemOptionFactory
     */
    public function __construct(
        \Magento\Quote\Model\Quote\Item\OptionFactory $itemOptionFactory
    ) {
        $this->itemOptionFactory = $itemOptionFactory;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param string $subscriptionInterval
     * @param string $subscriptionOption
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveQuoteItemOptions($quoteItem, $product, $platformProduct, $subscriptionInterval, $subscriptionOption)
    {
        if ($subscriptionOption != PlatformProductInterface::SO_SUBSCRIPTION) {
            $this->addQuoteItemOption($quoteItem, self::SUBSCRIPTION_CREATING, false);
            return;
        }

        $subscriptionInterval = $this->getSubscriptionInterval($subscriptionInterval, $platformProduct);
        $this->checkQuantity($quoteItem->getQty(), $platformProduct, $product);
        $this->checkInterval($subscriptionInterval, $platformProduct);

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
     * @param string $subscriptionInterval
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @return string|null
     */
    protected function getSubscriptionInterval($subscriptionInterval, $platformProduct)
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
