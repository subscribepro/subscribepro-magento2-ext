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
    public $quoteItemHelper;

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
        // $subscriptionOptions is an array that holds the subscription attributes of the quote item
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
}
