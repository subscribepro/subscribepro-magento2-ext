<?php

namespace Swarming\SubscribePro\Model\CatalogRule\Inspector;

use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;

class DefaultInspector implements InspectorInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\CatalogRule\Observer\RulePricesStorage
     */
    protected $rulePricesStorage;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\CatalogRule\Observer\RulePricesStorage $rulePricesStorage
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\CatalogRule\Observer\RulePricesStorage $rulePricesStorage
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->rulePricesStorage = $rulePricesStorage;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isApplied($product)
    {
        return $this->isAppliedToProduct($product);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    protected function isAppliedToProduct($product)
    {
        return $this->hasSpecialPrice($product) || $this->isAppliedCatalogRule($product);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    protected function hasSpecialPrice($product)
    {
        return $product->getPrice() != $product->getPriceModel()->getBasePrice($product);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    protected function isAppliedCatalogRule($product)
    {
        $productId = $product->getId();

        $storeId = $product->getStoreId();

        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        $customerGroupId = $product->hasCustomerGroupId()
            ? $product->getCustomerGroupId()
            : $this->customerSession->getCustomerGroupId();

        $date = $this->localeDate->scopeDate($storeId);

        $key = "{$date->format('Y-m-d H:i:s')}|{$websiteId}|{$customerGroupId}|{$productId}";
        return $this->rulePricesStorage->getRulePrice($key) > 0;
    }
}
