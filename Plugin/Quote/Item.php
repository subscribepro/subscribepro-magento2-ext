<?php

namespace Swarming\SubscribePro\Plugin\Quote;

use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;

class Item
{
    /**
     * @param \Magento\Quote\Model\Quote\Item $subject
     * @param \Closure $proceed
     * @param array $options1
     * @param array $options2
     * @return bool
     */
    public function aroundCompareOptions(
        \Magento\Quote\Model\Quote\Item $subject,
        \Closure $proceed,
        array $options1,
        array $options2
    ) {
        $result = $proceed($options1, $options2);
        if ($result) {
            $result = $this->compareOptions($options1, $options2);
        }
        return $result;
    }

    /**
     * @param array $options1
     * @param array $options2
     * @return bool
     */
    protected function compareOptions($options1, $options2)
    {
        $subscriptionOption1 = isset($options1['info_buyRequest']) ? $this->getParam($options1['info_buyRequest'], SubscriptionOptionInterface::OPTION) : null;
        $subscriptionOption2 = isset($options2['info_buyRequest']) ? $this->getParam($options2['info_buyRequest'], SubscriptionOptionInterface::OPTION) : null;
        if (empty($subscriptionOption1) && empty($subscriptionOption2)) {
            return true;
        }

        if ($subscriptionOption1 != $subscriptionOption2) {
            return false;
        }

        $subscriptionInterval1 = $this->getParam($options1['info_buyRequest'], SubscriptionOptionInterface::INTERVAL);
        $subscriptionInterval2 = $this->getParam($options2['info_buyRequest'], SubscriptionOptionInterface::INTERVAL);

        return $subscriptionInterval1 == $subscriptionInterval2;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\Option $buyRequest
     * @param string $paramKey
     * @return mixed|null
     */
    protected function getParam($buyRequest, $paramKey)
    {
        $params = $this->getSubscriptionParams($buyRequest);
        return isset($params[$paramKey]) ? $params[$paramKey] : null;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\Option $buyRequest
     * @return array
     */
    protected function getSubscriptionParams($buyRequest)
    {
        $buyRequest = $buyRequest ? unserialize($buyRequest->getValue()) : [];
        return isset($buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION]) ? $buyRequest[OptionProcessor::KEY_SUBSCRIPTION_OPTION] : [];
    }


}
