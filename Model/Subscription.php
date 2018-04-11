<?php

namespace Swarming\SubscribePro\Model;

use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;
use SubscribePro\Service\Subscription\Subscription as PlatformSubscription;

/**
 * @codeCoverageIgnore
 */
class Subscription extends PlatformSubscription implements SubscriptionInterface
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

    /**
     * @return mixed[]
     */
    public function getProductOption()
    {
        $platformSpecificFields = $this->getPlatformSpecificFields();
        return isset($platformSpecificFields[self::PLATFORM_FIELD_KEY][self::PRODUCT_OPTION])
            ? $platformSpecificFields[self::PLATFORM_FIELD_KEY][self::PRODUCT_OPTION]
            : [];
    }

    /**
     * @param array $productOptions
     * @return $this
     */
    public function setProductOption(array $productOptions)
    {
        $platformSpecificFields = $this->getPlatformSpecificFields();
        $platformSpecificFields[self::PLATFORM_FIELD_KEY][self::PRODUCT_OPTION] = $productOptions;
        return $this->setPlatformSpecificFields($platformSpecificFields);
    }

    public function getUserDefinedFields()
    {
       return json_encode(parent::getUserDefinedFields());
    }
}
