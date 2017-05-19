<?php

namespace Swarming\SubscribePro\Model\Rule\Condition;

/**
 * Class Product
 * @package Swarming\SubscribePro\Model\Rule\Condition
 * @method getAttribute()
 */
class Product extends \Magento\SalesRule\Model\Rule\Condition\Product
{
    const SUBSCRIPTION_STATUS_ANY = 0;
    const SUBSCRIPTION_STATUS_NEW = 1;
    const SUBSCRIPTION_STATUS_REORDER = 2;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        array $data = []
    ) {
        $this->quoteItemHelper = $quoteItemHelper;
        parent::__construct($context, $backendData, $config, $productFactory, $productRepository, $productResource, $attrSetCollection, $localeFormat, $data);
    }

    /**
     * Add special attributes
     *
     * @param array $attributes
     * @return void
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $attributes['quote_item_part_of_subscription'] = __('Subscription - Status');
        $attributes['quote_item_subscription_interval'] = __('Subscription - Current Interval');
        $attributes['quote_item_subscription_reorder_ordinal'] = __('Subscription - Re-order Ordinal');
    }

    /**
     * Validate Product Rule Condition
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        // Subscription Options is an array that holds the subscription attributes of the quote item
        $subscriptionOptions = $this->getSubscriptionOptions($model);
        if (
            !$subscriptionOptions['new_subscription']
            && !$subscriptionOptions['is_fulfilling']
            && !$subscriptionOptions['reorder_ordinal']
            && !$subscriptionOptions['interval']
        ) {
            return parent::validate($model);
        }
        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                // Check quote item attributes
                // Get value set on rule condition
                $conditionValue = $this->getValueParsed();
                // Get operator set on rule condition
                $op = $this->getOperatorForValidate();
                // Handle different status types
                switch ($conditionValue) {
                    case self::SUBSCRIPTION_STATUS_ANY:
                        $matchResult = ($subscriptionOptions['new_subscription'] || $subscriptionOptions['is_fulfilling']);
                        break;
                    case self::SUBSCRIPTION_STATUS_NEW:
                        $matchResult = $subscriptionOptions['new_subscription'];
                        break;
                    case self::SUBSCRIPTION_STATUS_REORDER:
                        $matchResult = $subscriptionOptions['is_fulfilling'];
                        break;
                    default:
                        $matchResult = false;
                        break;
                }
                // Since this attribute is a select list only == and != operators are allowed
                // In case of !=, do invert $matchResult
                if($op != '==') {
                    $matchResult = !$matchResult;
                }
                // Return our result
                return $matchResult;
            case 'quote_item_subscription_interval':
                // Check quote item attributes
                if ($subscriptionOptions['new_subscription'] || $subscriptionOptions['is_fulfilling']) {
                    return $this->validateAttribute($subscriptionOptions['interval']);
                } else {
                    return false;
                }
            case 'quote_item_subscription_reorder_ordinal':
                // Check quote item attributes
                if ($subscriptionOptions['new_subscription'] || $subscriptionOptions['is_fulfilling']) {
                    // This is a new subscription
                    return $this->validateAttribute($subscriptionOptions['reorder_ordinal']);
                } else {
                    return false;
                }
            default:
                return parent::validate($model);
        }
    }

    /**
     * Retrieve input type
     *
     * @return string
     */
    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                return 'select';
            case 'quote_item_subscription_interval':
                return 'string';
            case 'quote_item_subscription_reorder_ordinal':
                return 'string';
            default:
                return parent::getInputType();
        }
    }
    /**
     * Retrieve value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                return 'select';
            case 'quote_item_subscription_interval':
                return 'text';
            case 'quote_item_subscription_reorder_ordinal':
                return 'text';
            default:
                return parent::getValueElementType();
        }
    }

    /**
     * Retrieve the select options for the subscription status
     *
     * @return array
     */
    public function getValueSelectOptions()
    {
        switch ($this->getAttribute()) {
            case 'quote_item_part_of_subscription':
                return array(
                    array('value' => self::SUBSCRIPTION_STATUS_ANY, 'label' => __('Part of Subscription (New or Re-order)')),
                    array('value' => self::SUBSCRIPTION_STATUS_NEW, 'label' => __('Part of New Subscription Order')),
                    array('value' => self::SUBSCRIPTION_STATUS_REORDER, 'label' => __('Part of Subscription Re-order')),
                );
            default:
                return parent::getValueSelectOptions();
        }
    }

    /**
     * Helper that retrieves the subscription options associated with the quote
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return \Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface|null
     */
    protected function getSubscriptionOptions(\Magento\Framework\Model\AbstractModel $model)
    {
        // Initialize the return payload with default values
        $return = [
            'new_subscription' => false,
            'is_fulfilling' => false,
            'reorder_ordinal' => false,
            'interval' => false,
        ];

        // First we check if the Subscription Options are set on the qoute item, this only
        // happens when the quote items are in the checkout phase
        if (
            (
                $model instanceof \Magento\Quote\Model\Quote\Item\Interceptor ||
                $model instanceof \Magento\Quote\Model\Quote\Item
            )
            && $model->getProductOption()
            && $model->getProductOption()->getExtensionAttributes()
            && $model->getProductOption()->getExtensionAttributes()->getSubscriptionOption()
        ) {
            $subscriptionOptions = $model->getProductOption()->getExtensionAttributes()->getSubscriptionOption();
            $return['new_subscription'] = $subscriptionOptions->getCreatesNewSubscription();
            $return['is_fulfilling'] = $subscriptionOptions->getIsFulfilling();
            $return['reorder_ordinal'] = $subscriptionOptions->getReorderOrdinal();
            if ($return['reorder_ordinal'] == null && $return['new_subscription'] === true) {
                $return['reorder_ordinal'] = 0;
            }
            $return['interval'] = $subscriptionOptions->getInterval();
            return $return;
        }

        // If the quote item is not in the checkout phase we have to check the raw
        // subscription parameters
        $params = $this->quoteItemHelper->getSubscriptionParams($model);
        if (isset($params['option']) && $params['option'] == 'subscription') {
            $return['new_subscription'] = true;
            $return['is_fulfilling'] = false;
            $return['reorder_ordinal'] = 0;
            $return['interval'] = isset($params['interval']) ? $params['interval'] : false;
        }
        return $return;
    }
}
