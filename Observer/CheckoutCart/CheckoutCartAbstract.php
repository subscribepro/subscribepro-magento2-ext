<?php

namespace Swarming\SubscribePro\Observer\CheckoutCart;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;

abstract class CheckoutCartAbstract implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

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
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater $subscriptionOptionUpdater
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\State $appState
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater $subscriptionOptionUpdater,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $appState,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->generalConfig = $generalConfig;
        $this->platformProductManager = $platformProductManager;
        $this->subscriptionOptionUpdater = $subscriptionOptionUpdater;
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        $this->messageManager = $messageManager;
        $this->appState = $appState;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param array $quoteItemParams
     */
    protected function updateQuoteItem(QuoteItem $quoteItem, array $quoteItemParams)
    {
        $product = $quoteItem->getProduct();
        if ($quoteItem->getParentItem() && $quoteItem->getParentItem()->getProduct()) {
            $product = $quoteItem->getParentItem()->getProduct();
        }

        if (!$this->productHelper->isSubscriptionEnabled($product)) {
            return;
        }

        $platformProduct = $this->getPlatformProduct($product);
        if (!$platformProduct) {
            return;
        }

        $warnings = $this->subscriptionOptionUpdater->update(
            $quoteItem,
            $platformProduct,
            $this->getParamData($quoteItemParams, SubscriptionOptionInterface::OPTION),
            $this->getParamData($quoteItemParams, SubscriptionOptionInterface::INTERVAL)
        );

        foreach ($warnings as $message) {
            $this->messageManager->addWarningMessage($message);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPlatformProduct($product)
    {
        try {
            $platformProduct = $this->platformProductManager->getProduct($product->getData(ProductInterface::SKU));
        } catch (NoSuchEntityException $e) {
            if ($this->appState->getMode() === AppState::MODE_DEVELOPER) {
                throw $e;
            }
            $this->logger->critical($e->getLogMessage());
            $platformProduct = null;
        }
        return $platformProduct;
    }

    /**
     * @param array $params
     * @param string $key
     * @return string|null
     */
    protected function getParamData(array $params, $key)
    {
        return isset($params[$key]) ? $params[$key] : null;
    }
}
