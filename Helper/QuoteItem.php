<?php

namespace Swarming\SubscribePro\Helper;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;

class QuoteItem
{
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
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function hasQuoteSubscription($quote)
    {
        $hasSubscription = false;
        $items = (array)$quote->getItems();
        foreach ($items as $item) {
            if ($this->hasSubscription($item)) {
                $hasSubscription = true;
                break;
            }
        }
        return $hasSubscription;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    public function hasSubscription(AbstractItem $item)
    {
        return $this->isSubscriptionEnabled($item) || $this->isFulfilsSubscription($item);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    public function isSubscriptionEnabled(AbstractItem $item)
    {
        return $this->getSubscriptionOption($item) == PlatformProductInterface::SO_SUBSCRIPTION;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    public function isFulfilsSubscription(AbstractItem $item)
    {
        return (bool)$this->getParam($item, SubscriptionOptionInterface::IS_FULFILLING);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool|string
     */
    public function getSubscriptionOption(AbstractItem $item)
    {
        return $this->getParam($item, SubscriptionOptionInterface::OPTION);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool|string
     */
    public function getSubscriptionInterval(AbstractItem $item)
    {
        return $this->getParam($item, SubscriptionOptionInterface::INTERVAL);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param string $paramKey
     * @return bool
     */
    protected function getParam(AbstractItem $item, $paramKey)
    {
        $params = $this->getSubscriptionParams($item);
        return isset($params[$paramKey]) ? $params[$paramKey] : null;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param string $paramKey
     * @param string $paramValue
     * @return $this
     */
    public function setSubscriptionParam(AbstractItem $item, $paramKey, $paramValue)
    {
        $params = $this->getSubscriptionParams($item);
        $params[$paramKey] = $paramValue;
        $this->setSubscriptionParams($item, $params);
        $this->markQuoteItemAsModified($item);
        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return array
     */
    public function getSubscriptionParams(AbstractItem $item)
    {
        $buyRequest = $item->getOptionByCode('info_buyRequest');
        $buyRequest = $buyRequest ? unserialize($buyRequest->getValue()) : [];
        return isset($buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION]) ? $buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION] : [];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array $params
     * @return array
     */
    protected function setSubscriptionParams(AbstractItem $item, $params)
    {
        $buyRequestOption = !empty($item->getOptionByCode('info_buyRequest'))
            ? $item->getOptionByCode('info_buyRequest')
            : $this->itemOptionFactory->create()->setProduct($item->getProduct())->setCode('info_buyRequest');

        $buyRequest = $buyRequestOption->getValue() ? unserialize($buyRequestOption->getValue()) : [];

        $buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION] = $params;

        $buyRequestOption->setValue(serialize($buyRequest));
        $item->addOption($buyRequestOption);
    }

    /**
     * In case when only options are updated. Options are saved only if quote item is changed.
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem
     */
    protected function markQuoteItemAsModified(AbstractItem $quoteItem)
    {
        if (!$quoteItem->isObjectNew()) {
            $quoteItem->setUpdatedAt(date('Y-m-d H:i:s'));
        }
    }
}
