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
     * @var \Swarming\SubscribePro\Model\Config\Advanced
     */
    protected $advancedConfig;

    /**
     * @var \Swarming\SubscribePro\Api\Data\ProductInterface[]
     */
    private $platformProducts = [];

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $serializer;


    /**
     * @param \Magento\Framework\Cache\FrontendInterface $cache
     * @param \Magento\Framework\App\Cache\StateInterface $state
     * @param \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     */
    public function __construct(
        \Magento\Framework\Cache\FrontendInterface $cache,
        \Magento\Framework\App\Cache\StateInterface $state,
        \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\Serializer\Serialize $serializer
    ) {
        $this->cache = $cache;
        $this->state = $state;
        $this->advancedConfig = $advancedConfig;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
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

        $platformProduct = $this->serializer->unserialize($platformProductData);
        $this->platformProducts[$cacheKey] = $platformProduct;

        return $platformProduct;
    }

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $platformProduct
     * @param null|int $websiteId
     * @param null|int $lifeTime
     * @return void
     */
    public function save($platformProduct, $websiteId, $lifeTime = null)
    {
        $cacheKey = $this->getCacheKey($platformProduct->getSku(), $websiteId);
        $this->platformProducts[$cacheKey] = $platformProduct;

        if (!$this->state->isEnabled(PlatformProductCache::TYPE_IDENTIFIER)) {
            return;
        }

        $lifeTime = $lifeTime ?: $this->advancedConfig->getCacheLifeTime($websiteId);
        $this->cache->save($this->serializer->serialize($platformProduct), $cacheKey, [], $lifeTime);
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
        return self::PRODUCT_CACHE_KEY . '_' . hash('sha256', $this->serializer->serialize([$sku, $websiteCode]));
    }
}
