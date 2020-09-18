<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

use Magento\Rule\Model\Condition\Context;
use Swarming\SubscribePro\Helper\DiscountRule as DiscountRuleHelper;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;

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
     * @var QuoteItemHelper
     */
    protected $quoteItemHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var DiscountRuleHelper
     */
    protected $discountRuleHelper;

    /**
     * Constructor
     * @param Context $context
     * @param QuoteItemHelper $quoteItemHelper
     * @param DiscountRuleHelper $discountRuleHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        QuoteItemHelper $quoteItemHelper,
        DiscountRuleHelper $discountRuleHelper,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteItemHelper = $quoteItemHelper;
        $this->discountRuleHelper = $discountRuleHelper;
        $this->logger = $logger;
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
    protected function isNewSubscription(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->discountRuleHelper->isNewSubscription($this->quoteItemHelper->getSubscriptionParams($model));
    }

    /**
     * Is the quote item fulfilling an existing subscription
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function isItemFulfilsSubscription(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->discountRuleHelper->isItemFulfilsSubscription($this->quoteItemHelper->getSubscriptionParams($model));
    }

    /**
     * Is the quote item a new subscription or fulfilling an existing subscription
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function isItemNewOrFulfillingSubscription(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->isNewSubscription($model) || $this->isItemFulfilsSubscription($model);
    }

    /**
     * Does the quote item have a reorder ordinal?
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function hasReorderOrdinal(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->discountRuleHelper->hasReorderOrdinal($this->quoteItemHelper->getSubscriptionParams($model));
    }

    /**
     * Does the quote item have a reorder ordinal?
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return string|null
     */
    protected function getReorderOrdinal(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->discountRuleHelper->getReorderOrdinal($this->quoteItemHelper->getSubscriptionParams($model));
    }

    /**
     * Get the quote item interval
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return string|null
     */
    protected function getInterval(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->discountRuleHelper->getInterval($this->quoteItemHelper->getSubscriptionParams($model));
    }

    /**
     * Helper that retrieves the subscription options associated with the quote
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return array
     */
    protected function getSubscriptionOptions(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->discountRuleHelper->getSubscriptionOptions($this->quoteItemHelper->getSubscriptionParams($model));
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    protected function subscriptionOptionsAreFalse(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->discountRuleHelper->subscriptionOptionsAreFalse($this->quoteItemHelper->getSubscriptionParams($model));
    }
}
