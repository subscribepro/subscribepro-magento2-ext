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
        $subscriptionOptions = $this->getSubscriptionOptions($model);
        if ($subscriptionOptions === null) {
            // Subscription Options could be null if we are just viewing a cart before checkout
            return $this->validateCartItem($model);
        }

        return $this->validateCheckoutItem($model, $subscriptionOptions);
    }

    /**
     * Quote items that are contained within a cart before the checkout step are structured differently and need to be
     * checked differently
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     */
    protected function validateCartItem(\Magento\Framework\Model\AbstractModel $model)
    {
        $subscriptionOptions = $this->quoteItemHelper->getSubscriptionParams($model);
        if (!$subscriptionOptions) {
            return parent::validate($model);
        }
        switch($this->getAttribute()) {

            case 'quote_item_part_of_subscription':
                // Check quote item attributes
                // Get value set on rule condition
                $conditionValue = $this->getValueParsed();
                // Get operator set on rule condition
                $op = $this->getOperatorForValidate();
                // Handle different status types
                switch ($conditionValue) {
                    case self::SUBSCRIPTION_STATUS_ANY:
                    case self::SUBSCRIPTION_STATUS_NEW:
                        $matchResult = ($subscriptionOptions['option'] == 'subscription');
                        break;
                    case self::SUBSCRIPTION_STATUS_REORDER:
                        $matchResult = false;
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
                if ($subscriptionOptions['option'] == 'subscription') {
                    return parent::validateAttribute($subscriptionOptions['interval']);
                } else {
                    return false;
                }
            case 'quote_item_subscription_reorder_ordinal':
                if ($subscriptionOptions['option'] == 'subscription') {
                    return parent::validateAttribute(0);
                } else {
                    return false;
                }
            default:
                return parent::validate($model);
        }
        return parent::validate($model);
    }

    /**
     * Quote items during checkout have a different structure and need to be checked differently than before checkout
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @param $subscriptionOptions
     */
    protected function validateCheckoutItem(\Magento\Framework\Model\AbstractModel $model, $subscriptionOptions)
    {
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
                        $matchResult = ($subscriptionOptions->getCreatesNewSubscription() || $subscriptionOptions->getIsFulfilling());
                        break;
                    case self::SUBSCRIPTION_STATUS_NEW:
                        $matchResult = $subscriptionOptions->getCreatesNewSubscription();
                        break;
                    case self::SUBSCRIPTION_STATUS_REORDER:
                        $matchResult = $subscriptionOptions->getIsFulfilling();
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
                if ($subscriptionOptions->getCreatesNewSubscription() || $subscriptionOptions->getIsFulfilling()) {
                    return parent::validateAttribute($subscriptionOptions->getInterval());
                } else {
                    return false;
                }
            case 'quote_item_subscription_reorder_ordinal':
                // Check quote item attributes
                if ($subscriptionOptions->getCreatesNewSubscription()) {
                    // This is a new subscription
                    return $this->validateAttribute(0);
                }
                else if ($subscriptionOptions->getIsFulfilling()) {
                    // This is a recurring order on a subscription
                    return $this->validateAttribute($subscriptionOptions->getReorderOrdinal());
                }
                else {
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
        file_put_contents('/var/www/magento2/var/log/test.log', '------Product------' . "\n", FILE_APPEND | LOCK_EX);
        file_put_contents('/var/www/magento2/var/log/test.log',  print_r($this->quoteItemHelper->getSubscriptionParams($model), true) . "\n", FILE_APPEND | LOCK_EX);
        if ($model instanceof \Magento\Quote\Model\Quote\Item\Interceptor
            && $model->getProductOption()
            && $model->getProductOption()->getExtensionAttributes()
            && $model->getProductOption()->getExtensionAttributes()->getSubscriptionOption()
        ) {
            return $model->getProductOption()->getExtensionAttributes()->getSubscriptionOption();
        }
        return null;
    }
}
