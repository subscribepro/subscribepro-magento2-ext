<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Rule;

class ProductCondition extends \Swarming\SubscribePro\Model\Rule\Condition\Product
{
    public function exposedGetSubscriptionOptions(\Magento\Framework\Model\AbstractModel $model)
    {
        return $this->getSubscriptionOptions($model);
    }
}
