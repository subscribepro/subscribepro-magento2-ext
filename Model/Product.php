<?php

namespace Swarming\SubscribePro\Model;

use Swarming\SubscribePro\Api\Data\ProductInterface;

class Product extends \SubscribePro\Service\Product\Product implements ProductInterface
{
    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->getData(self::URL);
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @return float|null
     */
    public function getFinalPrice()
    {
        return $this->getData(self::FINAL_PRICE);
    }

    /**
     * @param float $finalPrice
     * @return $this
     */
    public function setFinalPrice($finalPrice)
    {
        return $this->setData(self::FINAL_PRICE, $finalPrice);
    }

    /**
     * @return bool|null
     */
    public function getApplyDiscountToCatalogPrice()
    {
        return $this->getData(self::APPLY_DISCOUNT_TO_CATALOG_PRICE);
    }

    /**
     * @param bool $applyDiscountToCatalogPrice
     * @return $this
     */
    public function setApplyDiscountToCatalogPrice($applyDiscountToCatalogPrice)
    {
        return $this->setData(self::APPLY_DISCOUNT_TO_CATALOG_PRICE, $applyDiscountToCatalogPrice);
    }

    /**
     * @return string|null
     */
    public function getImageUrl()
    {
        return $this->getData(self::IMAGE_URL);
    }

    /**
     * @param string $imageUrl
     * @return $this
     */
    public function setImageUrl($imageUrl)
    {
        return $this->setData(self::IMAGE_URL, $imageUrl);
    }
}
