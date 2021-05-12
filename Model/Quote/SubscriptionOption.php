<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Framework\Api\AbstractSimpleObject;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;

/**
 * @codeCoverageIgnore
 */
class SubscriptionOption extends AbstractSimpleObject implements SubscriptionOptionInterface
{
    /**
     * @return string|null
     */
    public function getInterval()
    {
        return $this->_get(self::INTERVAL);
    }

    /**
     * @param string $interval
     * @return $this
     */
    public function setInterval($interval)
    {
        return $this->setData(self::INTERVAL, $interval);
    }

    /**
     * @return bool
     */
    public function getIsFulfilling()
    {
        return (bool)$this->_get(self::IS_FULFILLING);
    }

    /**
     * @param bool $isFulfilling
     * @return $this
     */
    public function setIsFulfilling($isFulfilling)
    {
        $this->setData(self::IS_FULFILLING, $isFulfilling);
        return $this;
    }

    /**
     * @return bool
     */
    public function getItemFulfilsSubscription()
    {
        return (bool)$this->_get(self::ITEM_FULFILS_SUBSCRIPTION);
    }

    /**
     * @param bool $itemFulfilsSubscription
     * @return $this
     */
    public function setItemFulfilsSubscription($itemFulfilsSubscription)
    {
        $this->setData(self::ITEM_FULFILS_SUBSCRIPTION, $itemFulfilsSubscription);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSubscriptionId()
    {
        return $this->_get(self::SUBSCRIPTION_ID);
    }

    /**
     * @param int $subscriptionId
     * @return $this
     */
    public function setSubscriptionId($subscriptionId)
    {
        return $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    /**
     * @return string|null
     */
    public function getReorderOrdinal()
    {
        return $this->_get(self::REORDER_ORDINAL);
    }

    /**
     * @param string $reorderOrdinal
     * @return $this
     */
    public function setReorderOrdinal($reorderOrdinal)
    {
        return $this->setData(self::REORDER_ORDINAL, $reorderOrdinal);
    }

    /**
     * @param bool $createNewSubscriptionAtCheckout
     * @return $this
     */
    public function setCreateNewSubscriptionAtCheckout($createNewSubscriptionAtCheckout)
    {
        return $this->setData(self::CREATE_NEW_SUBSCRIPTION_AT_CHECKOUT, $createNewSubscriptionAtCheckout);
    }

    /**
     * @return bool
     */
    public function getCreateNewSubscriptionAtCheckout()
    {
        return $this->_get(self::CREATE_NEW_SUBSCRIPTION_AT_CHECKOUT);
    }

    /**
     * @param bool $itemAddedBySubscribePro
     * @return $this
     */
    public function setItemAddedBySubscribePro($itemAddedBySubscribePro)
    {
        return $this->setData(self::ITEM_ADDED_BY_SUBSCRIBE_PRO, $itemAddedBySubscribePro);
    }

    /**
     * @return bool
     */
    public function getItemAddedBySubscribePro()
    {
        return $this->_get(self::ITEM_ADDED_BY_SUBSCRIBE_PRO);
    }

    /**
     * @return string|null
     */
    public function getNextOrderDate()
    {
        return $this->_get(self::NEXT_ORDER_DATE);
    }

    /**
     * @param string $nextOrderDate
     * @return $this
     */
    public function setNextOrderDate($nextOrderDate)
    {
        return $this->setData(self::NEXT_ORDER_DATE, $nextOrderDate);
    }

    /**
     * @return string|null
     */
    public function getFixedPrice()
    {
        return $this->_get(self::FIXED_PRICE);
    }

    /**
     * @param string $fixedPrice
     * @return $this
     */
    public function setFixedPrice($fixedPrice)
    {
        return $this->setData(self::FIXED_PRICE, $fixedPrice);
    }

    /**
     * @return mixed[]
     */
    public function toArray()
    {
        return parent::__toArray();
    }
}
