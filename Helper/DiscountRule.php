<?php

namespace Swarming\SubscribePro\Helper;

class DiscountRule
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Is the quote item going to create a new subscription?
     *
     * @param array $params
     * @return bool
     */
    public function isNewSubscription(array $params)
    {
        return !empty($params['create_new_subscription_at_checkout']);
    }

    /**
     * Is the quote item fulfilling an existing subscription
     *
     * @param array $params
     * @return bool
     */
    public function isItemFulfilsSubscription(array $params)
    {
        return !empty($params['item_fulfils_subscription']);
    }

    /**
     * Is the quote item a new subscription or fulfilling an existing subscription
     *
     * @param array $params
     * @return bool
     */
    public function isItemNewOrFulfillingSubscription(array $params)
    {
        return $this->isNewSubscription($params) || $this->isItemFulfilsSubscription($params);
    }

    /**
     * Does the quote item have a reorder ordinal?
     *
     * @param array $params
     * @return bool
     */
    public function hasReorderOrdinal(array $params)
    {
        return !empty($params['reorder_ordinal']);
    }

    /**
     * Does the quote item have a reorder ordinal?
     *
     * @param array $params
     * @return string|null
     */
    public function getReorderOrdinal(array $params)
    {
        return $this->hasReorderOrdinal($params) ? $params['reorder_ordinal'] : null;
    }

    /**
     * Get the quote item interval
     *
     * @param array $params
     * @return string|null
     */
    public function getInterval($params)
    {
        return isset($params['interval']) ? $params['interval'] : null;
    }

    /**
     * Helper that retrieves the subscription options associated with the quote
     *
     * @param array $params
     * @return array
     */
    public function getSubscriptionOptions($params)
    {
        // Initialize the return payload with default values
        $return = [
            'new_subscription' => false,
            'is_fulfilling' => false,
            'reorder_ordinal' => false,
            'interval' => false,
        ];
        // The first and second possibilities of four. Either the subscription parameters are empty, IE
        // The product does not have a subscription option enabled, OR the user selected the one time purchase
        // option on a subscription product. For both of these we return the false array. I want to explicitly catch
        // this case to be clear what is happening.
        if (empty($params) || (isset($params['option']) && $params['option'] == 'onetime_purchase')) {
            return $return;
        }
        // The third of four possibilities: The cart item is a new subscription as denoted by the create_new_subscription_at_checkout
        // parameter set to subscription. We then set the ordinal to 0, as it is the first order, and set
        // the interval if it exists. (It really should exist here as a subscription without an interval
        // makes no sense.
        if (isset($params['create_new_subscription_at_checkout']) && $params['create_new_subscription_at_checkout']) {
            $return['new_subscription'] = true;
            $return['reorder_ordinal'] = 0;
            $return['interval'] = $this->getInterval($params);
            return $return;
        }
        // The fourth of four possibilities: The cart item contains a subscription product that is being fulfilled
        // We retrieve the ordinal and interval from the subscription parameters and set them if they exist.
        if (isset($params['item_fulfils_subscription']) && $params['item_fulfils_subscription']) {
            $return['is_fulfilling'] = true;
            $return['reorder_ordinal'] = $this->getReorderOrdinal($params);
            $return['interval'] = $this->getInterval($params);
            return $return;
        }
        // In case there is an unexpected parameter setup, just return the false array
        return $return;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function subscriptionOptionsAreFalse($params)
    {
        return !$this->isNewSubscription($params)
            && !$this->isItemFulfilsSubscription($params)
            && !$this->hasReorderOrdinal($params)
            && !$this->getInterval($params);
    }

    /**
     * @param array $subscriptionParams
     * @return false|string
     */
    public function validateReorderOrdinal($subscriptionParams)
    {
        // If the subscription parameters are not given
        // or if the item is not a new or recurring subscription order
        // or if there is no valid reorder ordinal set, then return false;
        // otherwise, return the reorder ordinal
        return !$this->subscriptionOptionsAreFalse($subscriptionParams)
        && $this->isItemNewOrFulfillingSubscription($subscriptionParams)
        && ($reorder_ordinal = $this->getReorderOrdinal($subscriptionParams))
            ? $reorder_ordinal
            : false;
    }

    /**
     * @param array $subscriptionParams
     * @return false|string
     */
    public function validateInterval($subscriptionParams)
    {
        // If the subscription parameters are not given
        // or if the item is not a new or recurring subscription order
        // or if there is no valid interval set, then return false;
        // otherwise, return the interval
        return !$this->subscriptionOptionsAreFalse($subscriptionParams)
        && $this->isItemNewOrFulfillingSubscription($subscriptionParams)
        && ($interval = $this->getInterval($subscriptionParams))
            ? $interval
            : false;
    }

    /**
     * @param $subscriptionParams
     * @param $conditionValue
     * @param $op
     * @return bool
     */
    public function validateStatus($subscriptionParams, $conditionValue, $op)
    {
        if ($this->subscriptionOptionsAreFalse($subscriptionParams)) {
            $matchResult = false;
        } else {
            $matchResult = $this->getStatusMatchResult($subscriptionParams, $conditionValue);
        }
        return $this->isNegateOperation($op) ? $this->getNegatedResult($matchResult) : $matchResult;
    }

    /**
     * @param array $subscriptionParams
     * @param string $conditionValue
     * @param string $op
     * @return bool
     */
    protected function getStatusMatchResult($subscriptionParams, $conditionValue)
    {
        // Handle different status types
        switch ($conditionValue) {
            case \Swarming\SubscribePro\Model\Rule\Condition\Base::SUBSCRIPTION_STATUS_ANY:
                $matchResult = $this->isItemNewOrFulfillingSubscription($subscriptionParams);
                break;
            case \Swarming\SubscribePro\Model\Rule\Condition\Base::SUBSCRIPTION_STATUS_NEW:
                $matchResult = $this->isNewSubscription($subscriptionParams);
                break;
            case \Swarming\SubscribePro\Model\Rule\Condition\Base::SUBSCRIPTION_STATUS_REORDER:
                $matchResult = $this->isItemFulfilsSubscription($subscriptionParams);
                break;
            default:
                $matchResult = false;
                break;
        }

        return $matchResult;
    }

    /**
     * @param string $op
     * @return bool
     * @throws \Exception
     */
    public function isNegateOperation($op)
    {
        switch ($op) {
            case '==':
                return false;
            case '!=':
                return true;
            default:
                throw new \Exception('Invalid cart rule operation ' . $op);
        }
    }

    /**
     * @param bool $result
     * @return bool
     */
    public function getNegatedResult($result)
    {
        return !$result;
    }
}
