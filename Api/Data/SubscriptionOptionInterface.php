<?php

namespace Swarming\SubscribePro\Api\Data;

/**
 * Subscription Option
 * @api
 */
interface SubscriptionOptionInterface
{
    const OPTION = 'option';

    const INTERVAL = 'interval';

    const ITEM_FULFILS_SUBSCRIPTION = 'item_fulfils_subscription';

    const IS_FULFILLING = 'is_fulfilling';

    const SUBSCRIPTION_ID = 'subscription_id';

    const REORDER_ORDINAL = 'reorder_ordinal';

    const CREATE_NEW_SUBSCRIPTION_AT_CHECKOUT = 'create_new_subscription_at_checkout';

    const ITEM_ADDED_BY_SUBSCRIBE_PRO = 'item_added_by_subscribe_pro';

    const NEXT_ORDER_DATE = 'next_order_date';

    const FIXED_PRICE = 'fixed_price';

    /**
     * @return string|null
     */
    public function getInterval();

    /**
     * @param string $interval
     * @return $this
     */
    public function setInterval($interval);

    /**
     * @return bool
     */
    public function getIsFulfilling();

    /**
     * @param bool $isFulfilling
     * @return $this
     */
    public function setIsFulfilling($isFulfilling);

    /**
     * @return bool
     */
    public function getItemFulfilsSubscription();

    /**
     * @param bool $itemFulfilsSubscription
     * @return $this
     */
    public function setItemFulfilsSubscription($itemFulfilsSubscription);

    /**
     * @return bool
     */
    public function getItemAddedBySubscribePro();

    /**
     * @param bool $itemAddedBySubscribePro
     * @return $this
     */
    public function setItemAddedBySubscribePro($itemAddedBySubscribePro);

    /**
     * @return int|null
     */
    public function getSubscriptionId();

    /**
     * @param int $subscriptionId
     * @return $this
     */
    public function setSubscriptionId($subscriptionId);

    /**
     * @return string
     */
    public function getReorderOrdinal();

    /**
     * @param string $reorderOrdinal
     * @return $this
     */
    public function setReorderOrdinal($reorderOrdinal);

    /**
     * @param bool $createNewSubscriptionAtCheckout
     * @return $this
     */
    public function setCreateNewSubscriptionAtCheckout($createNewSubscriptionAtCheckout);

    /**
     * @return bool
     */
    public function getCreateNewSubscriptionAtCheckout();

    /**
     * @param bool $nextOrderDate
     * @return $this
     */
    public function setNextOrderDate($nextOrderDate);

    /**
     * @return string
     */
    public function getNextOrderDate();

    /**
     * @param double $fixedPrice
     * @return $this
     */
    public function setFixedPrice($fixedPrice);

    /**
     * @return double|null
     */
    public function getFixedPrice();

    /**
     * @return mixed[]
     */
    public function __toArray();
}
