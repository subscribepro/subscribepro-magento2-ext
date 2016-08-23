<?php

namespace Swarming\SubscribePro\Api\Data;

/**
 * Subscribe Pro Product interface.
 *
 * @api
 */
interface ProductInterface extends \SubscribePro\Service\Product\ProductInterface
{
    /**
     * Constants used as data array keys
     */
    const URL = 'url';

    const IMAGE_URL = 'image_url';

    const FINAL_PRICE = 'final_price';

    const TAX_RATE = 'tax_rate';
    
    const DISCOUNT_TAX = 'discount_tax';

    const PRICE_INCLUDES_TAX = 'price_includes_tax';

    const DISPLAY_PRICE_INCLUDING_TAX = 'display_price_including_tax';

    const APPLY_DISCOUNT_TO_CATALOG_PRICE = 'apply_discount_to_catalog_price';

    const NEED_PRICE_CONVERSION = 'need_price_conversion';

    const APPLY_TAX_AFTER_DISCOUNT = 'apply_tax_after_discount';

    /**
     * @return string|null
     */
    public function getUrl();

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url);

    /**
     * @return float|null
     */
    public function getFinalPrice();

    /**
     * @param float $finalPrice
     * @return $this
     */
    public function setFinalPrice($finalPrice);
    
    /**
     * @return float|null
     */
    public function getTaxRate();

    /**
     * @param float $taxRate
     * @return $this
     */
    public function setTaxRate($taxRate);
    
    /**
     * @return bool|null
     */
    public function getPriceIncludesTax();

    /**
     * @param bool $priceIncludesTax
     * @return $this
     */
    public function setPriceIncludesTax($priceIncludesTax);

    /**
     * @return bool|null
     */
    public function getDisplayPriceIncludingTax();

    /**
     * @param bool $displayPriceIncludingTax
     * @return $this
     */
    public function setDisplayPriceIncludingTax($displayPriceIncludingTax);

    /**
     * @return bool|null
     */
    public function getDiscountTax();

    /**
     * @param bool $discountTax
     * @return $this
     */
    public function setDiscountTax($discountTax);

    /**
     * @return bool|null
     */
    public function getNeedPriceConversion();

    /**
     * @param bool $needPriceConversion
     * @return $this
     */
    public function setNeedPriceConversion($needPriceConversion);

    /**
     * @return bool|null
     */
    public function getApplyTaxAfterDiscount();

    /**
     * @param bool $applyTaxAfterDiscount
     * @return $this
     */
    public function setApplyTaxAfterDiscount($applyTaxAfterDiscount);

    /**
     * @return bool|null
     */
    public function getApplyDiscountToCatalogPrice();

    /**
     * @param bool $applyDiscountToCatalogPrice
     * @return $this
     */
    public function setApplyDiscountToCatalogPrice($applyDiscountToCatalogPrice);

    /**
     * @return string|null
     */
    public function getImageUrl();

    /**
     * @param string $imageUrl
     * @return $this
     */
    public function setImageUrl($imageUrl);
}
