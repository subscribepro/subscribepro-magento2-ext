<?php

namespace Swarming\SubscribePro\Helper;

use Swarming\SubscribePro\Model\Config\Source\CartRuleCombine;

class SalesRuleValidator
{
    public function isOnlySubscriptionDiscount($baseSubscriptionDiscount, $baseCartDiscount, $combineType)
    {
        $result = false;
        switch ($combineType) {
            case CartRuleCombine::TYPE_APPLY_GREATEST:
                if ($baseSubscriptionDiscount >= $baseCartDiscount) {
                    $result = true;
                }
                break;
            case CartRuleCombine::TYPE_APPLY_LEAST:
                if ($baseSubscriptionDiscount <= $baseCartDiscount) {
                    $result = true;
                }
                break;
            case CartRuleCombine::TYPE_APPLY_CART_DISCOUNT: /* Only If no cart rules applied */
                if ($baseCartDiscount == 0) {
                    $result = true;
                }
                break;
            case CartRuleCombine::TYPE_APPLY_SUBSCRIPTION:
                $result = true;
                break;
            default:
                $result = false;
                break;
        }
        return $result;
    }

    public function isCombineDiscounts($combineType)
    {
        return $combineType === CartRuleCombine::TYPE_COMBINE_SUBSCRIPTION;
    }

    public function getBaseSubscriptionDiscount($isDiscountPercentage, $discountAmount, $itemBasePrice, $qty)
    {
        // Whether the discount is fixed or percentage we need to multiply it by the quantity
        $subscriptionDiscount = $discountAmount * $qty;
        // If it is a percentage, we then multiply it by the base price
        if ($isDiscountPercentage) {
            $subscriptionDiscount *= $itemBasePrice;
        }

        // That may sound wrong but:
        // For a flat discount of course, if the amount is 5 and a quantity is 4 we want to discount 20, and we don't care what the base price is.
        // And for a percentage if the amount is 5% (0.05) and the quantity is 4 and the item base price is 20, we want to multiply 4*20 (80) and then multiply 80*.05 (4)
        // Since multiple multiplication operations can be done in any order it works out using the above logic
        return $subscriptionDiscount;
    }
}
