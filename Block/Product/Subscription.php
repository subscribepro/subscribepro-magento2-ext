<?php

namespace Swarming\SubscribePro\Block\Product;

use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;
use Magento\Tax\Model\Config as TaxConfig;

class Subscription extends \Magento\Catalog\Block\Product\AbstractProduct
{
    const TAX_CLASS_ID = 'tax_class_id';
    
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductService;

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

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
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->localeFormat = $localeFormat;
        $this->platformProductService = $platformProductService;
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->taxConfig = $taxConfig;
        $this->taxCalculation = $taxCalculation;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        if ($this->subscriptionDiscountConfig->isEnabled() && $this->isProductSubscriptionEnabled()) {
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
    public function getSubscriptionProduct()
    {
        $subscribeProProduct = $this->platformProductService->getProduct($this->getProduct()->getSku());
        $finalPrice = $this->getProduct()->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getValue();
        $regularPrice = $this->getProduct()->getPriceInfo()->getPrice(RegularPrice::PRICE_CODE)->getValue();
        $taxRate = $this->taxCalculation->getCalculatedRate($this->getProduct()->getCustomAttribute(self::TAX_CLASS_ID)->getValue());
        $displayPriceIncludingTax = $this->taxConfig->getPriceDisplayType() == TaxConfig::DISPLAY_TYPE_INCLUDING_TAX;
        if (!$subscribeProProduct->getIsDiscountPercentage()) {
            $discount = $this->priceCurrency->convertAndRound($subscribeProProduct->getDiscount(), true);
            $subscribeProProduct->setDiscount($discount);
        }
        $subscribeProProduct->setTaxRate($taxRate)
            ->setPriceIncludesTax($this->taxConfig->priceIncludesTax())
            ->setDisplayPriceIncludingTax($displayPriceIncludingTax)
            ->setNeedPriceConversion($this->taxConfig->needPriceConversion())
            ->setApplyTaxAfterDiscount($this->taxConfig->applyTaxAfterDiscount())
            ->setDiscountTax($this->taxConfig->discountTax())
            ->setFinalPrice($finalPrice)
            ->setPrice($regularPrice)
            ->setApplyDiscountToCatalogPrice($this->subscriptionDiscountConfig->doApplyDiscountToCatalogPrice());

        return $subscribeProProduct;
    }

    /**
     * @return bool
     */
    protected function isProductSubscriptionEnabled()
    {
        $attribute = $this->getProduct()->getCustomAttribute(SubscriptionModifier::SUBSCRIPTION_ENABLED);
        return $attribute && $attribute->getValue();
    }
}
