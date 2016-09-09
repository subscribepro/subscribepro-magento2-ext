<?php

namespace Swarming\SubscribePro\Observer\Catalog;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use SubscribePro\Exception\HttpException;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManager;

    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Swarming\SubscribePro\Helper\Product $productHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Swarming\SubscribePro\Helper\Product $productHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->generalConfig = $generalConfig;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->platformProductManager = $platformProductManager;
        $this->productHelper = $productHelper;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $observer->getData('product');

        if ($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            return;
        }

        foreach ($this->getProductWebsites() as $website) {
            $this->saveProduct($product->getSku(), $website);
        }
    }

    /**
     * @param string $sku
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @throws LocalizedException
     */
    protected function saveProduct($sku, $website)
    {
        if (!$this->generalConfig->isEnabled($website->getCode())) {
            return;
        }

        if (!($storeId = $this->getDefaultStoreId($website->getDefaultGroupId()))) {
            $this->logger->critical(__('Default store not found for website "%1"', $website->getName()));
            return;
        }

        $product = $this->productRepository->get($sku, false, $storeId);
        if (!$product || !$this->productHelper->isSubscriptionEnabled($product)) {
            return;
        }

        try {
            $this->platformProductManager->saveProduct($product, $website->getId());
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(
                __('Fail to save product on Subscribe Pro platform for website "%1".', $website->getName())
            );
        }
    }

    /**
     * @param int $defaultGroupId
     * @return int|null
     */
    protected function getDefaultStoreId($defaultGroupId)
    {
        $group = $this->storeManager->getGroup($defaultGroupId);
        return $group ? $group->getDefaultStoreId() : null;
    }

    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface[]
     */
    protected function getProductWebsites()
    {
        $websites = $this->storeManager->getWebsites(false);
        if ($websiteId = $this->storeManager->getStore()->getWebsiteId()) {
            $websites = array_intersect_key($websites, array_flip([$websiteId]));
        }

        return $websites;
    }
}
