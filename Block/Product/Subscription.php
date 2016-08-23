<?php

namespace Swarming\SubscribePro\Block\Product;

use Swarming\SubscribePro\Api\Data\ProductInterface;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Tax\Model\Config as TaxConfig;

class Subscription extends \Magento\Catalog\Block\Product\AbstractProduct
{
    const TAX_CLASS_ID = 'tax_class_id';

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductService;

    /**
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        array $data = []
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->platformProductService = $platformProductService;
        $this->taxConfig = $taxConfig;
        $this->taxCalculation = $taxCalculation;
        $this->priceCurrency = $priceCurrency;
        $this->localeFormat = $localeFormat;
        $this->productHelper = $productHelper;
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        if ($this->subscriptionDiscountConfig->isEnabled() && $this->productHelper->isSubscriptionEnabled($this->getProduct())) {
            $this->initJsLayout();
        } else {
            $this->setTemplate('');
        }
        return parent::_beforeToHtml();
    }

    protected function initJsLayout()
    {
        $data = [
            'components' => [
                'subscription-container' => [
                    'config' => [
                        'oneTimePurchaseOption' => ProductInterface::SO_ONETIME_PURCHASE,
                        'subscriptionOption' => ProductInterface::SO_SUBSCRIPTION,
                        'subscriptionOnlyMode' => ProductInterface::SOM_SUBSCRIPTION_ONLY,
                        'subscriptionAndOneTimePurchaseMode' => ProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                        'priceFormat' => $this->localeFormat->getPriceFormat(),
                        'productData' => $this->getSubscriptionProduct()->toArray(),
                    ]
                ]
            ]
        ];

        $this->jsLayout = array_merge_recursive($data, $this->jsLayout);
    }

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSubscriptionProduct()
    {
        $platformProduct = $this->platformProductService->getProduct($this->getProduct()->getSku());
        $priceInfo = $this->getProduct()->getPriceInfo();
        $taxRate = $this->taxCalculation->getCalculatedRate($this->getProduct()->getCustomAttribute(self::TAX_CLASS_ID)->getValue());

        if (!$platformProduct->getIsDiscountPercentage()) {
            $discount = $this->priceCurrency->convertAndRound($platformProduct->getDiscount(), true);
            $platformProduct->setDiscount($discount);
        }

        $platformProduct->setTaxRate($taxRate)
            ->setPriceIncludesTax($this->taxConfig->priceIncludesTax())
            ->setDisplayPriceIncludingTax($this->taxConfig->getPriceDisplayType() == TaxConfig::DISPLAY_TYPE_INCLUDING_TAX)
            ->setNeedPriceConversion($this->taxConfig->needPriceConversion())
            ->setApplyTaxAfterDiscount($this->taxConfig->applyTaxAfterDiscount())
            ->setDiscountTax($this->taxConfig->discountTax())
            ->setFinalPrice($priceInfo->getPrice(FinalPrice::PRICE_CODE)->getValue())
            ->setPrice($priceInfo->getPrice(RegularPrice::PRICE_CODE)->getValue())
            ->setApplyDiscountToCatalogPrice($this->subscriptionDiscountConfig->isApplyDiscountToCatalogPrice());

        return $platformProduct;
    }
}
