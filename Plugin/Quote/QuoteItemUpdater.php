<?php

namespace Swarming\SubscribePro\Plugin\Quote;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use SubscribePro\Service\Product\ProductInterface as PlatformProductInterface;

class QuoteItemUpdater
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater
     */
    protected $subscriptionOptionUpdater;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater $subscriptionOptionUpdater
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\State $appState
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater $subscriptionOptionUpdater,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $appState,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->platformProductManager = $platformProductManager;
        $this->subscriptionOptionUpdater = $subscriptionOptionUpdater;
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        $this->messageManager = $messageManager;
        $this->appState = $appState;
        $this->logger = $logger;
    }
    /**
     * @param \Magento\Quote\Model\Quote\Item\Updater $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param array $info
     * @return bool
     */
    public function aroundUpdate(
        \Magento\Quote\Model\Quote\Item\Updater $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item,
        array $info
    ) {
        $return = $proceed($item, $info);
        $this->updateAdminQuoteItem($item, $info);
        return $return;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param array $quoteItemParams
     */
    protected function updateAdminQuoteItem(QuoteItem $quoteItem, array $quoteItemParams)
    {
        if (!$this->getSubscriptionOption($quoteItemParams) || !$this->getInterval($quoteItemParams)) {
            return;
        }

        $product = $quoteItem->getProduct();
        if ($quoteItem->getParentItem() && $quoteItem->getParentItem()->getProduct()) {
            $product = $quoteItem->getParentItem()->getProduct();
        }

        if (!$this->productHelper->isSubscriptionEnabled($product)) {
            return;
        }

        $platformProduct = $this->getPlatformProduct($product, $quoteItem->getQuote()->getStore()->getWebsiteId());
        if (!$platformProduct) {
            return;
        }

        $warnings = $this->subscriptionOptionUpdater->update(
            $quoteItem,
            $platformProduct,
            $this->getSubscriptionOption($quoteItemParams),
            $this->getInterval($quoteItemParams)
        );

        foreach ($warnings as $message) {
            $this->messageManager->addWarningMessage($message);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param int|null $websiteId
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPlatformProduct($product, $websiteId = null)
    {
        try {
            $platformProduct = $this->platformProductManager->getProduct($product->getData(ProductInterface::SKU), $websiteId);
        } catch (NoSuchEntityException $e) {
            if ($this->appState->getMode() === AppState::MODE_DEVELOPER) {
                throw $e;
            }
            $this->logger->critical($e->getLogMessage());
            $platformProduct = null;
        }
        return $platformProduct;
    }

    protected function getSubscriptionOption(array $quoteItemParams)
    {
        if (
            !isset($quoteItemParams['admin_subscription_option']['option'])
            || !strlen($quoteItemParams['admin_subscription_option']['option'])
        ) {
            return false;
        }
        return $quoteItemParams['admin_subscription_option']['option'];
    }

    protected function getInterval(array $quoteItemParams)
    {
        if (!isset($quoteItemParams['admin_subscription_option']['interval'])) {
            return false;
        }
        return $quoteItemParams['admin_subscription_option']['interval'];
    }
}
