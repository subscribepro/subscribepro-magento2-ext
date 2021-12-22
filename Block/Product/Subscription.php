<?php

namespace Swarming\SubscribePro\Block\Product;

use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Tax\Model\Config as TaxConfig;
use Swarming\SubscribePro\Api\Data\ProductInterface;
use SubscribePro\Exception\InvalidArgumentException;
use SubscribePro\Exception\HttpException;

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
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig $priceConfigProvider
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Psr\Log\LoggerInterface $logger
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
        \Magento\Checkout\Model\Cart $cart,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;
        $this->platformProductManager = $platformProductManager;
        $this->priceConfigProvider = $priceConfigProvider;
        $this->taxCalculation = $taxCalculation;
        $this->priceCurrency = $priceCurrency;
        $this->productHelper = $productHelper;
        $this->cart = $cart;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isCart()
    {
        return (bool)$this->getData('is_cart');
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _beforeToHtml()
    {
        if ($this->subscriptionDiscountConfig->isEnabled()
            && $this->productHelper->isSubscriptionEnabled($this->getProduct())
        ) {
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
        try {
            $platformProduct = $this->getPlatformProduct()->toArray();
        } catch (InvalidArgumentException $e) {
            $this->logger->debug('Could not load product from Subscribe Pro platform.');
            $this->logger->info($e->getMessage());
            $platformProduct = [];
        } catch (HttpException $e) {
            $this->logger->debug('Could not load product from Subscribe Pro platform.');
            $this->logger->info($e->getMessage());
            $platformProduct = [];
        }

        $data = [
            'components' => [
                'subscription-container' => [
                    'config' => [
                        'oneTimePurchaseOption' => ProductInterface::SO_ONETIME_PURCHASE,
                        'subscriptionOption' => ProductInterface::SO_SUBSCRIPTION,
                        'subscriptionOnlyMode' => ProductInterface::SOM_SUBSCRIPTION_ONLY,
                        'subscriptionAndOneTimePurchaseMode' => ProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                        'product' => $platformProduct,
                        'priceConfig' => $this->priceConfigProvider->getConfig()
                    ]
                ]
            ]
        ];

        $jsLayout = array_merge_recursive($this->jsLayout, $data);
        if ($this->isPriceHidden()) {
            $class = 'Swarming_SubscribePro/js/view/product/subscription-msrp';
            $jsLayout['components']['subscription-container']['component'] = $class;
            $jsLayout['components']['subscription-container']['config']['msrpPrice'] = $this->getMsrpPrice();
        }

        $this->jsLayout = $jsLayout;
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
            $this->taxCalculation->getCalculatedRate(
                $this->getProduct()
                    ->getCustomAttribute(self::TAX_CLASS_ID)
                    ->getValue()
            )
        );

        if ($this->isCart()) {
            $this->updateSubscriptionParams($platformProduct);
        }

        return $platformProduct;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     */
    protected function updateSubscriptionParams($platformProduct)
    {
        $id = (int)$this->getRequest()->getParam('id');
        $quoteItem = $this->cart->getQuote()->getItemById($id);

        if ($this->getProduct()->getId() != $quoteItem->getProduct()->getId()) {
            return;
        }

        $subscriptionOption = $this->quoteItemHelper->getSubscriptionOption($quoteItem);
        if ($subscriptionOption) {
            $platformProduct->setDefaultSubscriptionOption($subscriptionOption);
        }

        $subscriptionInterval = $this->quoteItemHelper->getSubscriptionInterval($quoteItem);
        if ($subscriptionInterval) {
            $platformProduct->setDefaultInterval($subscriptionInterval);
        }
    }

    /**
     * @return bool
     */
    protected function isPriceHidden()
    {
        $product = $this->getProduct();

        try {
            /** @var \Magento\Msrp\Pricing\Price\MsrpPrice $msrpPriceType */
            $msrpPriceType = $product->getPriceInfo()->getPrice('msrp_price');
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return $msrpPriceType->canApplyMsrp($product) && $msrpPriceType->isMinimalPriceLessMsrp($product);
    }

    /**
     * @return float
     */
    protected function getMsrpPrice()
    {
        $msrp = $this->getProduct()->getMsrp();
        return $msrp ? $this->priceCurrency->convertAndRound($msrp) : 0;
    }
}
