<?php

namespace Swarming\SubscribePro\Platform\Storage;

use Swarming\SubscribePro\Platform\Cache\Type\Product as PlatformProductCache;

class Product
{
    const PRODUCT_CACHE_KEY = 'sp_platform_product';

    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    private $cache;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    private $state;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Cache
     */
    protected $cacheConfig;

    /**
     * @var \Swarming\SubscribePro\Api\Data\ProductInterface[]
     */
    private $platformProducts = [];

    /**
     * @param \Magento\Framework\Cache\FrontendInterface $cache
     * @param \Magento\Framework\App\Cache\StateInterface $state
     * @param \Swarming\SubscribePro\Model\Config\Cache $cacheConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Cache\FrontendInterface $cache,
        \Magento\Framework\App\Cache\StateInterface $state,
        \Swarming\SubscribePro\Model\Config\Cache $cacheConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->cache = $cache;
        $this->state = $state;
        $this->cacheConfig = $cacheConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $sku
     * @param null|int $websiteId
     * @return null|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    public function load($sku, $websiteId = null)
    {
        $cacheKey = $this->getCacheKey($sku, $websiteId);
        if (isset($this->platformProducts[$cacheKey])) {
            return $this->platformProducts[$cacheKey];
        }

        if (!$this->state->isEnabled(PlatformProductCache::TYPE_IDENTIFIER)) {
            return null;
        }

        $platformProductData = $this->cache->load($cacheKey);
        if (!$platformProductData) {
            return null;
        }

        $platformProduct = unserialize($platformProductData);
        $this->platformProducts[$cacheKey] = $platformProduct;

        return $platformProduct;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $product
     * @param null|int $websiteId
     * @param null|int $lifeTime
     * @return void
     */
    public function save($product, $websiteId, $lifeTime = null)
    {
        $cacheKey = $this->getCacheKey($product->getSku(), $websiteId);
        $this->platformProducts[$cacheKey] = $product;

        if (!$this->state->isEnabled(PlatformProductCache::TYPE_IDENTIFIER)) {
            return;
        }

        $lifeTime = $lifeTime ?: $this->cacheConfig->getCacheLifeTime($websiteId);
        $this->cache->save(serialize($product), $cacheKey, [], $lifeTime);
    }

    /**
     * @param string $sku
     * @param null|int $websiteId
     * @return void
     */
    public function remove($sku, $websiteId = null)
    {
        $cacheKey = $this->getCacheKey($sku, $websiteId);
        if (isset($this->platformProducts[$cacheKey])) {
            unset($this->platformProducts[$cacheKey]);
        }

        if ($this->state->isEnabled(PlatformProductCache::TYPE_IDENTIFIER)) {
            $this->cache->remove($cacheKey);
        }
    }

    /**
     * @param string $sku
     * @param null|int $websiteId
     * @return string
     */
    protected function getCacheKey($sku, $websiteId = null)
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        return self::PRODUCT_CACHE_KEY . '_' . md5(serialize([$sku, $websiteCode]));
    }
}
