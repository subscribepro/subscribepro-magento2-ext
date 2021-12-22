<?php

namespace Swarming\SubscribePro\Ui\ConfigProvider;

use Magento\Tax\Model\Config as TaxConfig;

class PriceConfig
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->taxConfig = $taxConfig;
        $this->localeFormat = $localeFormat;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'discountTax' => $this->taxConfig->discountTax(),
            'applyTaxAfterDiscount' => $this->taxConfig->applyTaxAfterDiscount(),
            'priceIncludesTax' => $this->taxConfig->priceIncludesTax(),
            'displayPriceExcludingTax'
                => $this->taxConfig->getPriceDisplayType() == TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX,
            'displayPriceIncludingTax'
                => $this->taxConfig->getPriceDisplayType() == TaxConfig::DISPLAY_TYPE_INCLUDING_TAX,
            'displayPriceBoth' => $this->taxConfig->getPriceDisplayType() == TaxConfig::DISPLAY_TYPE_BOTH,
            'applyDiscountToCatalogPrice' => $this->subscriptionDiscountConfig->isApplyDiscountToCatalogPrice(),
            'discountMessage' => $this->subscriptionDiscountConfig->getDiscountMessage(),
            'priceFormat' => $this->localeFormat->getPriceFormat(),
        ];
    }
}
