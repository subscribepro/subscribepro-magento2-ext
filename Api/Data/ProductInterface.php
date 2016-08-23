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

    const APPLY_DISCOUNT_TO_CATALOG_PRICE = 'apply_discount_to_catalog_price';

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
