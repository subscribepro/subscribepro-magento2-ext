<?php

namespace Swarming\SubscribePro\Block\Adminhtml\Order\Create\Items;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Api\Data\ProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;

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
        $this->sessionQuote = $sessionQuote;
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $wishlistFactory, $giftMessageSave, $taxConfig, $taxData, $messageHelper, $stockRegistry, $stockState, $data);
    }

    /**
     * Determines if a product has the subscription option enabled
     *
     * @param $quoteItem
     * @return bool
     */
    public function isSubscriptionProduct(Item $quoteItem) {
        return $this->productHelper->isSubscriptionEnabled($quoteItem->getProduct());
    }

    /**
     * Returns the subscription options that are set in the SubscribePro platform for this product
     *
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubscriptionProduct(Item $quoteItem)
    {
        $sku = $quoteItem->getProduct()->getData(ProductInterface::SKU);

        $subscriptionProduct = $this->platformProductManager->getProduct($sku, $this->sessionQuote->getStore()->getWebsiteId());
        return $subscriptionProduct->toArray();
    }

    /**
     * Returns the current set subscription parameters of a quote item in array form
     *
     * @param Item $quoteItem
     * @return array
     */
    public function getSubscriptionParameters(Item $quoteItem)
    {
        return [
            SubscriptionOptionInterface::OPTION => $this->quoteItemHelper->getSubscriptionOption($quoteItem),
            SubscriptionOptionInterface::CREATE_NEW_SUBSCRIPTION_AT_CHECKOUT => $this->quoteItemHelper->getCreateNewSubscriptionAtCheckout($quoteItem),
            SubscriptionOptionInterface::INTERVAL => $this->quoteItemHelper->getSubscriptionInterval($quoteItem)
        ];
    }
}
