<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Swarming\SubscribePro\Helper\SalesRuleValidator;
use Swarming\SubscribePro\Model\Config\Source\CartRuleCombine;
use Swarming\SubscribePro\Model\Rule\Condition\Base as ConditionBase;

class SalesRuleValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SalesRuleValidator
     */
    protected $salesRuleValidatorHelper;

    protected function setUp()
    {
        $this->salesRuleValidatorHelper = new SalesRuleValidator();
    }

    /**
     * @param bool $expected
     * @param float $baseSubscriptionDiscount
     * @param float $baseCartDiscount
     * @param string $combineType
     * @dataProvider getIsOnlySubscriptionDiscountDataProvider
     */
    public function testIsOnlySubscriptionDiscount($expected, $baseSubscriptionDiscount, $baseCartDiscount, $combineType)
    {
        $this->assertEquals($expected, $this->salesRuleValidatorHelper->isOnlySubscriptionDiscount($baseSubscriptionDiscount, $baseCartDiscount, $combineType));
    }

    /**
     * @return array
     */
    public function getIsOnlySubscriptionDiscountDataProvider()
    {
        return [
            'Apply greatest discount, subscription discount is greater' => [
                'expected' => true,
                'baseSubscriptionDiscount' => '10',
                'baseCartDiscount' => '5',
                'combineType' => CartRuleCombine::TYPE_APPLY_GREATEST
            ],
            'Apply greatest discount, cart discount is greater' => [
                'expected' => false,
                'baseSubscriptionDiscount' => '5',
                'baseCartDiscount' => '10',
                'combineType' => CartRuleCombine::TYPE_APPLY_GREATEST
            ],
            'Apply least discount, subscription discount is greater' => [
                'expected' => false,
                'baseSubscriptionDiscount' => '10',
                'baseCartDiscount' => '5',
                'combineType' => CartRuleCombine::TYPE_APPLY_LEAST
            ],
            'Apply least discount, cart discount is greater' => [
                'expected' => true,
                'baseSubscriptionDiscount' => '5',
                'baseCartDiscount' => '10',
                'combineType' => CartRuleCombine::TYPE_APPLY_LEAST
            ],
            'Apply cart discount, cart discount = 0' => [
                'expected' => true,
                'baseSubscriptionDiscount' => '10',
                'baseCartDiscount' => '0',
                'combineType' => CartRuleCombine::TYPE_APPLY_CART_DISCOUNT
            ],
            'Apply cart discount, cart discount > 0' => [
                'expected' => false,
                'baseSubscriptionDiscount' => '5',
                'baseCartDiscount' => '10',
                'combineType' => CartRuleCombine::TYPE_APPLY_CART_DISCOUNT
            ],
            'Apply subscription discount, cart discount = 0' => [
                'expected' => true,
                'baseSubscriptionDiscount' => '10',
                'baseCartDiscount' => '0',
                'combineType' => CartRuleCombine::TYPE_APPLY_SUBSCRIPTION
            ],
            'Apply subscription discount, cart discount > 0' => [
                'expected' => true,
                'baseSubscriptionDiscount' => '5',
                'baseCartDiscount' => '10',
                'combineType' => CartRuleCombine::TYPE_APPLY_SUBSCRIPTION
            ],
            'Combine discounts' => [
                'expected' => false,
                'baseSubscriptionDiscount' => '10',
                'baseCartDiscount' => '20',
                'combineType' => CartRuleCombine::TYPE_COMBINE_SUBSCRIPTION
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param bool $isDiscountPercentage
     * @param float $discountAmount
     * @param float $itemBasePrice
     * @param int $qty
     * @dataProvider getGetBaseSubscriptionDiscountDataProvider
     */
    public function testGetBaseSubscriptionDiscount($expected, $isDiscountPercentage, $discountAmount, $itemBasePrice, $qty)
    {
        $this->assertEquals($expected, $this->salesRuleValidatorHelper->getBaseSubscriptionDiscount($isDiscountPercentage, $discountAmount, $itemBasePrice, $qty));
    }

    /**
     * @return array
     */
    public function getGetBaseSubscriptionDiscountDataProvider()
    {
        return [
            'Percentage discount, quantity 1' => [
                'expected' => 0.5,
                'isDiscountPercentage' => true,
                'discountAmount' => 0.05,
                'itemBasePrice' => 10,
                'qty' => 1,
            ],
            'Percentage discount, quantity 5' => [
                'expected' => 2.5,
                'isDiscountPercentage' => true,
                'discountAmount' => 0.05,
                'itemBasePrice' => 10,
                'qty' => 5,
            ],
            'Fixed discount, quantity 1' => [
                'expected' => 5,
                'isDiscountPercentage' => false,
                'discountAmount' => 5,
                'itemBasePrice' => 100,
                'qty' => 1,
            ],
            'Fixed discount, quantity 5' => [
                'expected' => 25,
                'isDiscountPercentage' => false,
                'discountAmount' => 5,
                'itemBasePrice' => 100,
                'qty' => 5,
            ],
        ];
    }
}
