<?php

namespace Swarming\SubscribePro\Block\Adminhtml\Order\Create\Items;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
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

    /**
     * @param $quoteItem
     * @return bool
     */
    public function isSubscriptionProduct(Item $quoteItem) {
        return $this->productHelper->isSubscriptionEnabled($quoteItem->getProduct());
    }

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubscriptionProduct(Item $quoteItem)
    {
        $sku = $quoteItem->getProduct()->getData(ProductInterface::SKU);
        $subscriptionProduct = $this->platformProductManager->getProduct($sku);

        if ($intervalOption = $this->quoteItemHelper->getSubscriptionInterval($quoteItem)) {
            $subscriptionProduct->setDefaultInterval($intervalOption);
        }

        $subscriptionOption = $this->quoteItemHelper->getSubscriptionOption($quoteItem) ?: PlatformProductInterface::SO_ONETIME_PURCHASE;
        $subscriptionProduct->setDefaultSubscriptionOption($subscriptionOption);

        return $subscriptionProduct->toArray();
    }

    public function getSubscriptionParameters(Item $quoteItem)
    {
        return [
            'option' => $this->quoteItemHelper->getSubscriptionOption($quoteItem),
            'interval' => $this->quoteItemHelper->getSubscriptionInterval($quoteItem)
        ];
    }
}
