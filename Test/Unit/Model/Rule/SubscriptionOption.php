<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Rule;

// Mock helper class
class SubscriptionOption
{
    public function getCreateNewSubscriptionAtCheckout()
    {
        return false;
    }

    public function getItemFulfillsSubscription()
    {
        return false;
    }

    public function getReorderOrdinal()
    {
        return false;
    }

    public function getInterval()
    {
        return false;
    }

    public function getNextOrderDate()
    {
        return false;
    }

    public function getFixedPrice()
    {
        return false;
    }
}