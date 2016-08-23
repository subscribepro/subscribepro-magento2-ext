<?php

namespace Swarming\SubscribePro\Observer\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use SubscribePro\Exception\HttpException;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $configGeneral;

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
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Swarming\SubscribePro\Platform\Manager\Product $platformProductManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configGeneral = $configGeneral;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->platformProductManager = $platformProductManager;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $event->getData('product');

        if ($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            return;
        }

        foreach ($this->getProductWebsites() as $website) {
            if (!$this->configGeneral->isEnabled($website->getCode())) {
                continue;
            }

            if (!($storeId = $this->getDefaultStoreId($website))) {
                $this->logger->critical(__('Default store not found for website "%1"', $website->getName()));
                continue;
            }

            $product = $this->productRepository->get($product->getSku(), false, $storeId);
            if (!$product || !$this->isProductSubscriptionEnabled($product)) {
                continue;
            }

            $this->saveProduct($product, $website);
        }
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @throws LocalizedException
     */
    protected function saveProduct($product, $website)
    {
        try {
            $this->platformProductManager->saveMagentoProduct($product, $website->getId());
        } catch (HttpException $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('Fail to save product on Subscribe Pro platform for website "%1".', $website->getName()));
        }
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
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @return int|null
     */
    protected function getDefaultStoreId($website)
    {
        $group = $this->storeManager->getGroup($website->getDefaultGroupId());
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
