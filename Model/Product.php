<?php

namespace Swarming\SubscribePro\Model;

use Swarming\SubscribePro\Api\Data\ProductInterface;

/**
 * @codeCoverageIgnore
 */
class Product extends \SubscribePro\Service\Product\Product implements ProductInterface
{
    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->getData(self::URL, '');
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

    /**
     * @return string[]
     */
    public function getOptionList()
    {
        return $this->getData(self::OPTION_LIST, []);
    }

    /**
     * @param array $optionList
     * @return $this
     */
    public function setOptionList(array $optionList)
    {
        return $this->setData(self::OPTION_LIST, $optionList);
    }

    /**
     * @return float|null
     */
    public function getFinalPrice()
    {
        return $this->getData(self::FINAL_PRICE, 0);
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
        return $this->getData(self::TAX_RATE, 0);
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
     * @return bool
     */
    public function getIsCatalogRuleApplied()
    {
        return $this->getData(self::IS_CATALOG_RULE_APPLIED, false);
    }

    /**
     * @param bool $isCatalogRuleApplied
     * @return $this
     */
    public function setIsCatalogRuleApplied($isCatalogRuleApplied)
    {
        return $this->setData(self::IS_CATALOG_RULE_APPLIED, $isCatalogRuleApplied);
    }
}
