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
     * @return float|null
     */
    public function getTaxRate()
    {
        return $this->getData(self::TAX_RATE);
    }

    /**
     * @param float $taxRate
     * @return $this
     */
    public function setTaxRate($taxRate)
    {
        return $this->setData(self::TAX_RATE, $taxRate);
    }

    /**
     * @return bool|null
     */
    public function getPriceIncludesTax()
    {
        return $this->getData(self::PRICE_INCLUDES_TAX);
    }

    /**
     * @param bool $priceIncludesTax
     * @return $this
     */
    public function setPriceIncludesTax($priceIncludesTax)
    {
        return $this->setData(self::PRICE_INCLUDES_TAX, $priceIncludesTax);
    }

    /**
     * @return bool|null
     */
    public function getDisplayPriceIncludingTax()
    {
        return $this->getData(self::DISPLAY_PRICE_INCLUDING_TAX);
    }

    /**
     * @param bool $displayPriceIncludingTax
     * @return $this
     */
    public function setDisplayPriceIncludingTax($displayPriceIncludingTax)
    {
        return $this->setData(self::DISPLAY_PRICE_INCLUDING_TAX, $displayPriceIncludingTax);
    }

    /**
     * @return bool|null
     */
    public function getDiscountTax()
    {
        return $this->getData(self::DISCOUNT_TAX);
    }

    /**
     * @param bool $discountTax
     * @return $this
     */
    public function setDiscountTax($discountTax)
    {
        return $this->setData(self::DISCOUNT_TAX, $discountTax);
    }

    /**
     * @return bool|null
     */
    public function getNeedPriceConversion()
    {
        return $this->getData(self::NEED_PRICE_CONVERSION);
    }

    /**
     * @param bool $needPriceConversion
     * @return $this
     */
    public function setNeedPriceConversion($needPriceConversion)
    {
        return $this->setData(self::NEED_PRICE_CONVERSION, $needPriceConversion);
    }

    /**
     * @return bool|null
     */
    public function getApplyTaxAfterDiscount()
    {
        return $this->getData(self::APPLY_TAX_AFTER_DISCOUNT);
    }

    /**
     * @param bool $applyTaxAfterDiscount
     * @return $this
     */
    public function setApplyTaxAfterDiscount($applyTaxAfterDiscount)
    {
        return $this->setData(self::APPLY_TAX_AFTER_DISCOUNT, $applyTaxAfterDiscount);
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
