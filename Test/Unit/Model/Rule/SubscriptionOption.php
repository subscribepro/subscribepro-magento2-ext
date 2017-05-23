<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Rule;

// Mock helper class
class SubscriptionOption
{
    public function getCreatesNewSubscription()
    {
        return false;
    }

    public function getIsFulfilling()
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
}