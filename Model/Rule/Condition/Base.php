<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

/**
 * Class Status
 * @package Swarming\SubscribePro\Model\Rule\Condition
 */
class Base extends \Magento\Rule\Model\Condition\AbstractCondition
{

    const SUBSCRIPTION_STATUS_ANY = 0;
    const SUBSCRIPTION_STATUS_NEW = 1;
    const SUBSCRIPTION_STATUS_REORDER = 2;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * Constructor
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteItemHelper = $quoteItemHelper;
    }

    /**
     * Get input type
     * @return string
     */
    public function getInputType()
    {
        return 'string';
    }

    /**
     * Get value element type
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * Is the quote item going to create a new subscription?
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function isNewSubscription(\Magento\Framework\Model\AbstractModel $model) {
        $params = $this->quoteItemHelper->getSubscriptionParams($model);
        return isset($params['create_new_subscription_at_checkout']) ? $params['create_new_subscription_at_checkout'] : false;
    }

    /**
     * Is the quote item fulfilling an existing subscription
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function isItemFulfilsSubscription(\Magento\Framework\Model\AbstractModel $model) {
        $params = $this->quoteItemHelper->getSubscriptionParams($model);
        return isset($params['item_fulfils_subscription']) ? $params['item_fulfils_subscription'] : false;
    }

    /**
     * Is the quote item a new subscription or fulfilling an existing subscription
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function isItemNewOrFulfillingSubscription(\Magento\Framework\Model\AbstractModel $model) {
        return $this->isNewSubscription($model) || $this->isItemFulfilsSubscription($model);
    }

    /**
     * Does the quote item have a reorder ordinal?
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function hasReorderOrdinal(\Magento\Framework\Model\AbstractModel $model) {
        $params = $this->quoteItemHelper->getSubscriptionParams($model);
        return isset($params['reorder_ordinal']);
    }

    /**
     * Does the quote item have a reorder ordinal?
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return string|null
     */
    protected function getReorderOrdinal(\Magento\Framework\Model\AbstractModel $model) {
        $params = $this->quoteItemHelper->getSubscriptionParams($model);
        return $this->hasReorderOrdinal($model) ? $params['reorder_ordinal'] : null;
    }

    /**
     * Get the quote item interval
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return string|null
     */
    protected function getInterval(\Magento\Framework\Model\AbstractModel $model) {
        $params = $this->quoteItemHelper->getSubscriptionParams($model);
        return isset($params['interval']) ? $params['interval'] : null;
    }

    /**
     * Helper that retrieves the subscription options associated with the quote
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return array
     */
    protected function getSubscriptionOptions(\Magento\Framework\Model\AbstractModel $model)
    {
        $params = $this->quoteItemHelper->getSubscriptionParams($model);
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
        if (
            empty($params)
            || (isset($params['option']) && $params['option'] == 'onetime_purchase')
        ) {
            return $return;
        }
        // The third of four possibilities: The cart item is a new subscription as denoted by the option
        // parameter set to subscription. We then set the ordinal to 0, as it is the first order, and set
        // the interval if it exists. (It really should exist here as a subscription without an interval
        // makes no sense.
        if (isset($params['option']) && $params['option'] == 'subscription') {
            $return['new_subscription'] = true;
            $return['reorder_ordinal'] = 0;
            $return['interval'] = isset($params['interval']) ? $params['interval'] : false;
            return $return;
        }
        // The fourth of four possibilities: The cart item contains a subscription product that is being fulfilled
        // We retrieve the ordinal and interval from the subscription parameters and set them if they exist.
        if (isset($params['is_fulfilling']) && $params['is_fulfilling']) {
            $return['is_fulfilling'] = true;
            $return['reorder_ordinal'] = isset($params['reorder_ordinal']) ? $params['reorder_ordinal'] : false;
            $return['interval'] = isset($params['interval']) ? $params['interval'] : false;
            return $return;
        }
        // In case there is an unexpected parameter setup, just return the false array
        return $return;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function subscriptionOptionsAreFalse(\Magento\Framework\Model\AbstractModel $model)
    {
        return !$this->isNewSubscription($model)
            && !$this->isItemFulfilsSubscription($model)
            && !$this->hasReorderOrdinal($model)
            && !$this->getInterval($model);
    }
}