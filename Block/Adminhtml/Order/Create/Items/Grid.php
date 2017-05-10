<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Swarming\SubscribePro\Block\Adminhtml\Order\Create\Items;

use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Api\Data\ProductInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;

/**
 * Adminhtml sales order create items grid block
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Grid extends \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid
{
    /**
     * @var \Swarming\SubscribePro\Helper\Product $productHelper
     */
    protected $productHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\GiftMessage\Model\Save $giftMessageSave
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\GiftMessage\Helper\Message $messageHelper
     * @param StockRegistryInterface $stockRegistry
     * @param StockStateInterface $stockState
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\GiftMessage\Model\Save $giftMessageSave,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\GiftMessage\Helper\Message $messageHelper,
        StockRegistryInterface $stockRegistry,
        StockStateInterface $stockState,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        array $data = []
    ) {
        $this->productHelper = $productHelper;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->platformProductManager = $platformProductManager;
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $wishlistFactory, $giftMessageSave, $taxConfig, $taxData, $messageHelper, $stockRegistry, $stockState, $data);
    }

    public function isSubscriptionProduct($quoteItem) {
        return $this->productHelper->isSubscriptionEnabled($quoteItem->getProduct());
    }

    public function getSubscriptionJsLayout($quoteItem) {
        $subscriptionContainerId = 'subscription-container-' . $quoteItem->getId();
        $subscriptionContainerComponent = [
            'config' => [
                'oneTimePurchaseOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionOnlyMode' => PlatformProductInterface::SOM_SUBSCRIPTION_ONLY,
                'subscriptionAndOneTimePurchaseMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'product' => $this->getSubscriptionProduct($quoteItem)->toArray(),
                'quoteItemId' => $quoteItem->getId(),
            ]
        ];
        $subscriptionContainerComponent = array_merge_recursive($subscriptionContainerComponent, (array)$this->getData('subscription-container-component'));

        $jsLayout = [
            'components' => [$subscriptionContainerId => $subscriptionContainerComponent]
        ];
        return $jsLayout;
    }

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubscriptionProduct($quoteItem)
    {
        $sku = $quoteItem->getProduct()->getData(ProductInterface::SKU);
        $subscriptionProduct = $this->platformProductManager->getProduct($sku);

        if ($intervalOption = $this->quoteItemHelper->getSubscriptionInterval($quoteItem)) {
            $subscriptionProduct->setDefaultInterval($intervalOption);
        }

        $subscriptionOption = $this->quoteItemHelper->getSubscriptionOption($quoteItem) ?: PlatformProductInterface::SO_ONETIME_PURCHASE;
        $subscriptionProduct->setDefaultSubscriptionOption($subscriptionOption);

        return $subscriptionProduct;
    }
}
