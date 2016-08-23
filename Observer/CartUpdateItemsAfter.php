<?php

namespace Swarming\SubscribePro\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Bundle\Model\Product\Type as BundleProductType;
use Swarming\SubscribePro\Model\Quote\ItemOptionsManager;
use SubscribePro\Service\Product\ProductInterface as PlatformProductInterface;

class CartUpdateItemsAfter implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $configGeneral;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\ItemOptionsManager
     */
    protected $quoteItemOptionsManager;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected  $quoteItemHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->configGeneral = $configGeneral;
        $this->checkoutSession = $checkoutSession;
        $this->quoteItemOptionsManager = $quoteItemOptionManager;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->productRepository = $productRepository;
        $this->messageManager = $messageManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->configGeneral->isEnabled() || !($quote = $this->checkoutSession->getQuote())) {
            return;
        }

        /** @var \Magento\Framework\DataObject $infoDataObject */
        $infoDataObject = $observer->getData('info');

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $quoteItemParams = $infoDataObject->getData($quoteItem->getItemId());
            $this->markQuoteItemAsModified($quoteItem);

            $this->quoteItemOptionsManager->addQuoteItemOptions(
                $quoteItem,
                $this->productRepository->get($this->getProductSku($quoteItem->getProduct())),
                $this->getParamData($quoteItemParams, ItemOptionsManager::OPTION_SUBSCRIPTION_INTERVAL),
                $this->getParamData($quoteItemParams, ItemOptionsManager::OPTION_SUBSCRIPTION_OPTION),
                $this->getCatchCallback($quoteItem)
            );
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return callable
     */
    protected function getCatchCallback($quoteItem)
    {
        return function (LocalizedException $e, PlatformProductInterface $platformProduct) use ($quoteItem) {
            $this->messageManager->addErrorMessage($e->getMessage());

            if ($platformProduct->getSubscriptionOptionMode() != PlatformProductInterface::SOM_SUBSCRIPTION_ONLY) {
                $this->quoteItemOptionsManager->addQuoteItemOption(
                    $quoteItem,
                    ItemOptionsManager::SUBSCRIPTION_CREATING,
                    false
                );
                return;
            }

            $originInterval = $this->quoteItemHelper->getSubscriptionInterval($quoteItem);
            $this->quoteItemOptionsManager->addQuoteItemOption(
                $quoteItem,
                ItemOptionsManager::SUBSCRIPTION_INTERVAL,
                $originInterval
            );
            $quoteItem->setQty($quoteItem->getOrigData(CartItemInterface::KEY_QTY));
        };
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

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    private function getProductSku($product)
    {
        /* TODO Use parent product SKU only */
        return $product->getTypeId() == BundleProductType::TYPE_CODE
            ? $product->getData(ProductInterface::SKU)
            : $product->getSku();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     */
    protected function markQuoteItemAsModified($quoteItem)
    {
        $quoteItem->setUpdatedAt(date('Y-m-d H:i:s'));
    }
}
