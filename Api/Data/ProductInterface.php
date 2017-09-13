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

    const OPTION_LIST = 'option_list';

    const FINAL_PRICE = 'final_price';

    const TAX_RATE = 'tax_rate';

    const IS_CATALOG_RULE_APPLIED = 'is_catalog_rule_applied';
    
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
     * @return string|null
     */
    public function getImageUrl();

    /**
     * @param string $imageUrl
     * @return $this
     */
    public function setImageUrl($imageUrl);

    /**
     * @return mixed[]
     */
    public function getOptionList();

    /**
     * @param array $optionList
     * @return $this
     */
    public function setOptionList(array $optionList);

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
     * @return bool
     */
    public function getIsCatalogRuleApplied();

    /**
     * @param bool $isCatalogRuleApplied
     * @return $this
     */
    public function setIsCatalogRuleApplied($isCatalogRuleApplied);
}
