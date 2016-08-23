<?php

namespace Swarming\SubscribePro\Model;

use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;

class Subscription extends \SubscribePro\Service\Subscription\Subscription implements SubscriptionInterface
{
    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface|null
     */
    public function getProduct()
    {
        return $this->getData(self::PRODUCT);
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product)
    {
        return $this->setData(self::PRODUCT, $product);
    }
}
