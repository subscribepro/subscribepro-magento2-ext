<?php

namespace Swarming\SubscribePro\Api\Data;

/**
 * Subscribe Pro Subscription interface.
 *
 * @api
 */
interface SubscriptionInterface extends \SubscribePro\Service\Subscription\SubscriptionInterface
{
    /**
     * Constants used as data array keys
     */
    const PRODUCT = 'product';

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface|null
     */
    public function getProduct();

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product);
}
