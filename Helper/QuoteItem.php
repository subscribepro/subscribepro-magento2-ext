<?php

namespace Swarming\SubscribePro\Helper;

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
     * @var \Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactory;

    /**
     * @param \Magento\Quote\Model\Quote\Item\OptionFactory $itemOptionFactory
     * @param \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        \Magento\Quote\Model\Quote\Item\OptionFactory $itemOptionFactory,
        \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
    ) {
        $this->itemOptionFactory = $itemOptionFactory;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function hasSubscription($item)
    {
        return $this->isSubscriptionEnabled($item) || $this->isFulfilsSubscription($item);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isSubscriptionEnabled($item)
    {
        return $this->getSubscriptionOption($item) == PlatformProductInterface::SO_SUBSCRIPTION;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isFulfilsSubscription($item)
    {
        return (bool)$this->getParam($item, SubscriptionOptionInterface::IS_FULFILLING);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return null|string
     */
    public function getSubscriptionOption($item)
    {
        return $this->getParam($item, SubscriptionOptionInterface::OPTION);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return null|string
     */
    public function getSubscriptionInterval($item)
    {
        return $this->getParam($item, SubscriptionOptionInterface::INTERVAL);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param string $paramKey
     * @return mixed|null
     */
    protected function getParam($item, $paramKey)
    {
        $params = $this->getSubscriptionParams($item);
        return isset($params[$paramKey]) ? $params[$paramKey] : null;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param string $paramKey
     * @param string $paramValue
     * @return $this
     */
    public function setSubscriptionParam($item, $paramKey, $paramValue)
    {
        $params = $this->getSubscriptionParams($item);
        $params[$paramKey] = $paramValue;
        $this->setSubscriptionParams($item, $params);
        $this->markQuoteItemAsModified($item);
        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return array
     */
    public function getSubscriptionParams($item)
    {
        $buyRequest = $item->getOptionByCode('info_buyRequest');
        $buyRequest = $buyRequest ? unserialize($buyRequest->getValue()) : [];
        return isset($buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION]) ? $buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION] : [];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param array $params
     * @return array
     */
    protected function setSubscriptionParams($item, $params)
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
     * @param \Magento\Quote\Model\Quote\Item $item
     */
    protected function markQuoteItemAsModified($item)
    {
        if (!$item->isObjectNew()) {
            $item->setUpdatedAt($this->dateTimeFactory->create()->format('Y-m-d H:i:s'));
        }
    }
}
