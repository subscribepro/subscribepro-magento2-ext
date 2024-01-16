<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Product;

use Magento\Catalog\Block\Product\Context;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Api\TaxCalculationInterface;
use Psr\Log\LoggerInterface;
use SubscribePro\Exception\HttpException;
use SubscribePro\Exception\InvalidArgumentException;
use SubscribePro\Service\Product\ProductInterface as ProductInterfaceAlias;
use Swarming\SubscribePro\Helper\Product;
use Swarming\SubscribePro\Helper\QuoteItem;
use Swarming\SubscribePro\Model\Config\SubscriptionDiscount;
use Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig;

class Grouped extends Subscription
{
    /**
     * @var Product
     */
    private Product $helper;

    /**
     * @param Context $context
     * @param SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param TaxCalculationInterface $taxCalculation
     * @param PriceConfig $priceConfigProvider
     * @param PriceCurrencyInterface $priceCurrency
     * @param Product $productHelper
     * @param Cart $cart
     * @param QuoteItem $quoteItemHelper
     * @param LoggerInterface $logger
     * @param Product $helper
     * @param array $data
     */
    public function __construct(
        Context                                         $context,
        SubscriptionDiscount                            $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        TaxCalculationInterface                         $taxCalculation,
        PriceConfig                                     $priceConfigProvider,
        PriceCurrencyInterface                          $priceCurrency,
        Product                                         $productHelper,
        Cart                                            $cart,
        QuoteItem                                       $quoteItemHelper,
        LoggerInterface                                 $logger,
        Product                                         $helper,
        array                                           $data = []
    ) {
        $this->helper = $helper;
        parent::__construct(
            $context,
            $subscriptionDiscountConfig,
            $platformProductManager,
            $taxCalculation,
            $priceConfigProvider,
            $priceCurrency,
            $productHelper,
            $cart,
            $quoteItemHelper,
            $logger,
            $data
        );
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    protected function initJsLayout(): void
    {
        try {
            $platformProduct = $this->getPlatformProduct()->toArray();
        } catch (InvalidArgumentException|HttpException $e) {
            $this->logger->error('Could not load product from Subscribe Pro platform.');
            $this->logger->error($e->getMessage());
            $platformProduct = [];
        }

        $data = [
            'components' => [
                'subscription-container-' . $this->getProduct()->getId() => [
                    'component' => 'Swarming_SubscribePro/js/view/product/grouped-subscription',
                    'config' => [
                        'oneTimePurchaseOption' => ProductInterfaceAlias::SO_ONETIME_PURCHASE,
                        'subscriptionOption' => ProductInterfaceAlias::SO_SUBSCRIPTION,
                        'subscriptionOnlyMode' => ProductInterfaceAlias::SOM_SUBSCRIPTION_ONLY,
                        'subscriptionAndOneTimePurchaseMode' => ProductInterfaceAlias::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                        'product' => $platformProduct,
                        'product_id' => $this->getProduct()->getId(),
                        'priceConfig' => $this->priceConfigProvider->getConfig(),
                        'template' => 'Swarming_SubscribePro/product/grouped-subscription',
                        'priceBoxSelector' => '.price-box',
                        'messages' => [
                            'component' => 'Magento_Ui/js/view/messages',
                            'displayArea' => 'messages',
                        ],
                    ]
                ]
            ]
        ];

        $jsLayout = array_replace_recursive($this->jsLayout, $data);
        if ($this->isPriceHidden()) {
            $class = 'Swarming_SubscribePro/js/view/product/subscription-msrp';
            $jsLayout['components']['subscription-container-' . $this->getProduct()->getId()]['component'] = $class;
            $jsLayout['components']['subscription-container-' . $this->getProduct()->getId()]['config']['msrpPrice'] = $this->getMsrpPrice();
        }

        $this->jsLayout = $jsLayout;
    }

    /**
     * @param $item
     * @return bool
     */
    public function isSubscriptionEnabled($item): bool
    {
        return $this->helper->isSubscriptionEnabled($item);
    }
}
