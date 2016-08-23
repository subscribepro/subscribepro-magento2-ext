<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Framework\Api\AbstractSimpleObject;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;

class SubscriptionOption extends AbstractSimpleObject implements SubscriptionOptionInterface
{
    /**
     * @return string|null
     */
    public function getOption()
    {
        return $this->_get(self::OPTION);
    }

    /**
     * @param string $option
     * @return $this
     */
    public function setOption($option)
    {
        return $this->setData(self::OPTION, $option);
    }

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
}
