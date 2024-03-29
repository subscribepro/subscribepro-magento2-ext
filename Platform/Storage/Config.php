<?php

namespace Swarming\SubscribePro\Platform\Storage;

use Swarming\SubscribePro\Platform\Cache\Type\Config as PlatformConfigCache;

class Config
{
    public const CONFIG_CACHE_KEY = 'sp_platform_config';

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
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;

    /**
     * @var array[]
     */
    private $configByWebsite = [];

    /**
     * @param \Magento\Framework\Cache\FrontendInterface $cache
     * @param \Magento\Framework\App\Cache\StateInterface $state
     * @param \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     */
    public function __construct(
        \Magento\Framework\Cache\FrontendInterface $cache,
        \Magento\Framework\App\Cache\StateInterface $state,
        \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->cache = $cache;
        $this->state = $state;
        $this->advancedConfig = $advancedConfig;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    /**
     * @param null|int $websiteId
     * @return null|array
     */
    public function load($websiteId = null)
    {
        $cacheKey = $this->getCacheKey($websiteId);
        if (isset($this->configByWebsite[$cacheKey])) {
            return $this->configByWebsite[$cacheKey];
        }

        if (!$this->state->isEnabled(PlatformConfigCache::TYPE_IDENTIFIER)) {
            return null;
        }

        $configData = $this->cache->load($cacheKey);
        if (!$configData) {
            return null;
        }

        $config = $this->serializer->unserialize($configData);
        $this->configByWebsite[$cacheKey] = $config;

        return $config;
    }

    /**
     * @param array $config
     * @param null|int $websiteId
     * @param null|int $lifeTime
     * @return bool
     */
    public function save(array $config, $websiteId, $lifeTime = null)
    {
        $cacheKey = $this->getCacheKey($websiteId);
        $this->configByWebsite[$cacheKey] = $config;

        if (!$this->state->isEnabled(PlatformConfigCache::TYPE_IDENTIFIER)) {
            return true;
        }

        $lifeTime = $lifeTime ?: (int)$this->advancedConfig->getCacheLifeTime($websiteId);
        return $this->cache->save($this->serializer->serialize($config), $cacheKey, [], $lifeTime);
    }

    /**
     * @param null|int $websiteId
     * @return string
     */
    protected function getCacheKey($websiteId = null)
    {
        return self::CONFIG_CACHE_KEY . '_' . $this->storeManager->getWebsite($websiteId)->getCode();
    }
}
