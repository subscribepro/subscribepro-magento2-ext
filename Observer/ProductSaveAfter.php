<?php

namespace Swarming\SubscribePro\Observer;

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
     * @var \Swarming\SubscribePro\Platform\Helper\ProductFactory
     */
    protected $platformProductHelperFactory;

    /**
     * @var \Magento\ConfigurableProduct\Api\LinkManagementInterface
     */
    protected $linkManagement;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $configGeneral
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\ConfigurableProduct\Api\LinkManagementInterface $linkManagement
     * @param \Swarming\SubscribePro\Platform\Helper\ProductFactory $platformProductHelperFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\ConfigurableProduct\Api\LinkManagementInterface $linkManagement,
        \Swarming\SubscribePro\Platform\Helper\ProductFactory $platformProductHelperFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configGeneral = $configGeneral;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->platformProductHelperFactory = $platformProductHelperFactory;
        $this->linkManagement = $linkManagement;
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

        $websites = $this->storeManager->getWebsites(false);
        foreach ($websites as $website) {
            if (!$this->configGeneral->isEnabled($website->getCode())) {
                continue;
            }

            $group = $this->storeManager->getGroup($website->getDefaultGroupId());
            if (!$group || $group->getDefaultStoreId() === null) {
                $this->logger->critical(__('Default store not found for website "%1"', $website->getName()));
                continue;
            }

            $product = $this->productRepository->get($product->getSku(), false, $group->getDefaultStoreId());
            if (!$product || !$this->isProductSubscriptionEnabled($product)) {
                continue;
            }

            $this->saveProduct($product, $website);

            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                foreach ($this->linkManagement->getChildren($product->getSku()) as $childProduct) {
                    $this->saveProduct($childProduct, $website);
                }
            }
        }
        
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @throws LocalizedException
     */
    protected function saveProduct($product, $website)
    {
        $productHelper = $this->platformProductHelperFactory->create(['websiteCode' => $website->getCode()]);
        try {
            $productHelper->saveProduct($product);
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
}
