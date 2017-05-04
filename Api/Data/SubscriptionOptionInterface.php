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

    const IS_FULFILLING = 'is_fulfilling';

    const SUBSCRIPTION_ID = 'subscription_id';

    const REORDER_ORDINAL = 'reorder_ordinal';

    /**
     * @return string|null
     */
    public function getOption();

    /**
     * @param string $option
     * @return $this
     */
    public function setOption($option);

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
     * @return mixed[]
     */
    public function __toArray();
}
