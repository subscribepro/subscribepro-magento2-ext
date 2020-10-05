<?php

namespace Swarming\SubscribePro\Model\Quote\SubscriptionOption;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use SubscribePro\Service\Product\ProductInterface as PlatformProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;

class Updater
{
    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var array
     */
    protected $warnings = [];

    /**
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param string $subscriptionOption
     * @param string $subscriptionInterval
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update($quoteItem, $platformProduct, $subscriptionOption, $subscriptionInterval)
    {
        $createNewSubscriptionAtCheckout = false;
        if (PlatformProductInterface::SO_SUBSCRIPTION == $this->getSubscriptionOption($platformProduct, $subscriptionOption)) {
            $this->validateIntervals($platformProduct);
            $this->validateQuantity($quoteItem, $platformProduct);
            $subscriptionInterval = $this->getSubscriptionInterval($quoteItem, $platformProduct, $subscriptionInterval);
            $createNewSubscriptionAtCheckout = true;
        }

        if (!$createNewSubscriptionAtCheckout) {
            $subscriptionInterval = null;
        }

        $this->quoteItemHelper->setSubscriptionParam($quoteItem, SubscriptionOptionInterface::OPTION, $subscriptionOption);
        $this->quoteItemHelper->setSubscriptionParam($quoteItem, SubscriptionOptionInterface::CREATE_NEW_SUBSCRIPTION_AT_CHECKOUT, $createNewSubscriptionAtCheckout);
        $this->quoteItemHelper->setSubscriptionParam($quoteItem, SubscriptionOptionInterface::INTERVAL, $subscriptionInterval);

        return $this->getWarnings();
    }

    /**
     * @param \SubscribePro\Service\Product\ProductInterface $platformProduct
     * @param string|null $subscriptionOption
     * @return string
     */
    protected function getSubscriptionOption(PlatformProductInterface $platformProduct, $subscriptionOption = null)
    {
        if ($platformProduct->getSubscriptionOptionMode() == PlatformProductInterface::SOM_SUBSCRIPTION_ONLY) {
            return PlatformProductInterface::SO_SUBSCRIPTION;
        } else if (!$this->validateIntervals($platformProduct, true)) {
            return PlatformProductInterface::SO_ONETIME_PURCHASE;
        }
        return $subscriptionOption ?: $platformProduct->getDefaultSubscriptionOption();
    }

    /**
     * @param \SubscribePro\Service\Product\ProductInterface $platformProduct
     * @param bool $graceful
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateIntervals($platformProduct, $graceful = false)
    {
        if (!empty($platformProduct->getIntervals())) {
            return true;
        } else if ($graceful) {
            return false;
        }
        throw new LocalizedException(__('The product is not configured properly, please contact customer support.'));
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param string|null $subscriptionInterval
     * @return string
     */
    protected function getSubscriptionInterval($quoteItem, $platformProduct, $subscriptionInterval = null)
    {
        $subscriptionInterval = $this->validateInterval($platformProduct, $subscriptionInterval)
            ? $subscriptionInterval
            : null;

        if (!$subscriptionInterval) {
            $subscriptionInterval = $this->quoteItemHelper->getSubscriptionInterval($quoteItem);
        }

        if (!$subscriptionInterval) {
            $subscriptionInterval = $platformProduct->getDefaultInterval();
        }

        if (!$subscriptionInterval) {
            $subscriptionInterval = $platformProduct->getIntervals()[0];
        }

        return $subscriptionInterval;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param string|null $subscriptionInterval
     * @return null|string
     * @throws \Exception
     */
    protected function validateInterval($platformProduct, $subscriptionInterval)
    {
        if (!empty($subscriptionInterval) && !in_array($subscriptionInterval, $platformProduct->getIntervals())) {
            $this->warnings[] = __('Subscription interval is not valid.');
            return false;
        }
        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     */
    protected function validateQuantity($quoteItem, $platformProduct)
    {
        $qty = $this->checkQuantity($quoteItem, $platformProduct);
        if ($quoteItem->getQty() == $qty) {
            return;
        }

        $originalQty = $quoteItem->getOrigData(CartItemInterface::KEY_QTY);

        $qty = empty($originalQty) ? $qty : $originalQty;

        $quoteItem->setQty($qty);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @return float
     */
    protected function checkQuantity($quoteItem, $platformProduct)
    {
        $qty = $quoteItem->getQty();
        if (($platformProduct->getMinQty() && $quoteItem->getQty() < $platformProduct->getMinQty())) {
            $this->warnings[] = __(
                'Product "%1" requires minimum quantity of %2 for subscription.',
                $quoteItem->getProduct()->getName(),
                $platformProduct->getMinQty()
            );
            $qty = $platformProduct->getMinQty();
        } else if ($platformProduct->getMaxQty() && $quoteItem->getQty() > $platformProduct->getMaxQty()) {
            $this->warnings[] = __(
                'Product "%1" allows maximum quantity of %2 for subscription.',
                $quoteItem->getProduct()->getName(),
                $platformProduct->getMaxQty()
            );
            $qty = $platformProduct->getMaxQty();
        }
        return $qty;
    }

    /**
     * @return array
     */
    protected function getWarnings()
    {
        $warnings = $this->warnings;
        $this->warnings = [];
        return $warnings;
    }
}
