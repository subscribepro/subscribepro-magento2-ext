<?php

namespace Swarming\SubscribePro\Block\Product;

use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Tax\Model\Config as TaxConfig;
use Swarming\SubscribePro\Api\Data\ProductInterface;

class Subscription extends \Magento\Catalog\Block\Product\AbstractProduct
{
    const TAX_CLASS_ID = 'tax_class_id';

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    protected $taxCalculation;

    /**
     * @var \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig
     */
    protected $priceConfigProvider;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig $priceConfigProvider
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig $priceConfigProvider,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        array $data = []
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->platformProductManager = $platformProductManager;
        $this->priceConfigProvider = $priceConfigProvider;
        $this->taxCalculation = $taxCalculation;
        $this->priceCurrency = $priceCurrency;
        $this->productHelper = $productHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _beforeToHtml()
    {
        if ($this->subscriptionDiscountConfig->isEnabled() && $this->productHelper->isSubscriptionEnabled($this->getProduct())) {
            $this->initJsLayout();
        } else {
            $this->setTemplate('');
        }
        return parent::_beforeToHtml();
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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
                        'product' => $this->getPlatformProduct()->toArray(),
                        'priceConfig' => $this->priceConfigProvider->getConfig()
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
    protected function getPlatformProduct()
    {
        $platformProduct = $this->platformProductManager->getProduct($this->getProduct()->getSku());
        if (!$platformProduct->getIsDiscountPercentage()) {
            $discount = $this->priceCurrency->convertAndRound($platformProduct->getDiscount(), true);
            $platformProduct->setDiscount($discount);
        }

        $priceInfo = $this->getProduct()->getPriceInfo();
        $platformProduct->setPrice($priceInfo->getPrice(RegularPrice::PRICE_CODE)->getValue());
        $platformProduct->setFinalPrice($priceInfo->getPrice(FinalPrice::PRICE_CODE)->getValue());
        $platformProduct->setTaxRate(
            $this->taxCalculation->getCalculatedRate($this->getProduct()->getCustomAttribute(self::TAX_CLASS_ID)->getValue())
        );

        return $platformProduct;
    }
}
