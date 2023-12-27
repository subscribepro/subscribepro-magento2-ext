<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Product;

use Magento\Framework\Exception\NoSuchEntityException;
use SubscribePro\Exception\HttpException;
use SubscribePro\Exception\InvalidArgumentException;
use SubscribePro\Service\Product\ProductInterface as ProductInterfaceAlias;
use Swarming\SubscribePro\Helper\Product;

class Grouped extends Subscription
{
    private $helper;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig $priceConfigProvider,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Psr\Log\LoggerInterface $logger,
        Product $helper,
        array $data = []
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
            $this->logger->debug('Could not load product from Subscribe Pro platform.');
            $this->logger->info($e->getMessage());
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
