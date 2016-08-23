<?php

namespace Swarming\SubscribePro\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Swarming\SubscribePro\Model\Quote\ItemOptionsManager;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;
use Swarming\SubscribePro\Api\Data\ProductInterface as SubscriptionProductInterface;

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
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductService;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Platform\Service\Product $platformProductService
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Platform\Service\Product $platformProductService,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Swarming\SubscribePro\Model\Quote\ItemOptionsManager $quoteItemOptionManager
    ) {
        $this->configGeneral = $configGeneral;
        $this->checkoutSession = $checkoutSession;
        $this->platformProductService = $platformProductService;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->messageManager = $messageManager;
        $this->quoteItemOptionsManager = $quoteItemOptionManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if (!$this->configGeneral->isEnabled() || !$this->checkoutSession->getQuote()) {
            return;
        }

        $event = $observer->getEvent();
        /** @var \Magento\Framework\DataObject $infoDataObject */
        $infoDataObject = $event->getData('info');
        $products = $this->getProducts($this->getProductsSku($this->checkoutSession->getQuote()));
        foreach ($this->checkoutSession->getQuote()->getAllVisibleItems() as $quoteItem) {
            $quoteItemRequestParams = $infoDataObject->getData($quoteItem->getItemId());
            $subscriptionOption = $this->getParamData($quoteItemRequestParams, ItemOptionsManager::OPTION_SUBSCRIPTION_OPTION);
            $interval = $this->getParamData($quoteItemRequestParams, ItemOptionsManager::OPTION_SUBSCRIPTION_INTERVAL);
            $product = $products[$this->getProductSku($quoteItem->getProduct())];
            $intervalOption = $quoteItem->getOptionByCode(ItemOptionsManager::SUBSCRIPTION_INTERVAL);
            $originInterval = $intervalOption ? $intervalOption->getValue() : null;
            if (!$this->isProductSubscriptionEnabled($product)) {
                continue;
            }

            try {
                $platformProduct = $this->platformProductService->getProduct($product->getSku());
            } catch (NoSuchEntityException $e) {
                throw new NoSuchEntityException(__('Product "%1" is not found on Subscribe Pro platform.', $quoteItem->getProduct()->getName()));
            }

            $this->markQuoteItemAsModified($quoteItem);
            try {
                $this->quoteItemOptionsManager->saveQuoteItemOptions($quoteItem, $product, $platformProduct, $interval, $subscriptionOption);
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
                if ($platformProduct->getSubscriptionOptionMode() != SubscriptionProductInterface::SOM_SUBSCRIPTION_ONLY) {
                    $this->quoteItemOptionsManager->addQuoteItemOption($quoteItem, ItemOptionsManager::SUBSCRIPTION_CREATING, false);
                    continue;
                }

                $this->quoteItemOptionsManager->addQuoteItemOption($quoteItem, ItemOptionsManager::SUBSCRIPTION_INTERVAL, $originInterval);
                $quoteItem->setQty($quoteItem->getOrigData(\Magento\Quote\Api\Data\CartItemInterface::KEY_QTY));
            }
        }
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
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    protected function isProductSubscriptionEnabled(ProductInterface $product)
    {
        $attribute = $product->getCustomAttribute(SubscriptionModifier::SUBSCRIPTION_ENABLED);
        return $attribute && $attribute->getValue();
    }

    /**
     * @param array $productsSku
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    protected function getProducts(array $productsSku)
    {
        if (empty($productsSku)) {
            return [];
        }

        $this->searchCriteriaBuilder->addFilter(ProductInterface::SKU, $productsSku, 'in');
        $productList = $this->productRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        $products = array_fill_keys($productsSku, null);
        foreach ($productList as $product) {
            $products[$product->getSku()] = $product;
        }
        return $products;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return string[]
     */
    protected function getProductsSku(Quote $quote)
    {
        $productsSku = array_map(function(QuoteItem $quoteItem) {
            return $this->getProductSku($quoteItem->getProduct());
        }, $quote->getAllVisibleItems());
        return array_unique($productsSku);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    private function getProductSku($product)
    {
        $sku = $product->getSku();
        if ($product->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            $sku = $product->getData(ProductInterface::SKU);
        }
        return $sku;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     */
    protected function markQuoteItemAsModified($quoteItem)
    {
        $quoteItem->setUpdatedAt(date('Y-m-d H:i:s'));
    }
}
