<?php

namespace Swarming\SubscribePro\Platform\Tool;

/**
 * @method \SubscribePro\Tools\Config getTool($websiteId = null)
 */
class Config extends AbstractTool
{
    /**
     * @var \Swarming\SubscribePro\Platform\Storage\Config
     */
    protected $platformStorageConfig;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param string $name
     * @param \Swarming\SubscribePro\Platform\Storage\Config $platformStorageConfig
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform,
        $name,
        \Swarming\SubscribePro\Platform\Storage\Config $platformStorageConfig
    ) {
        parent::__construct($platform, $name);
        $this->platformStorageConfig = $platformStorageConfig;
    }

    /**
     * @param string|null $key
     * @param int $websiteId
     * @return array|string
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getConfig($key = null, $websiteId = null)
    {
        $config = $this->retrieveConfig($websiteId);
        return null === $key
            ? $config
            : (isset($config[$key]) ? $config[$key] : null);
    }

    /**
     * @param int|null $websiteId
     * @return array|null
     */
    protected function retrieveConfig($websiteId = null)
    {
        $config = $this->platformStorageConfig->load($websiteId);
        if (!$config) {
            $config = $this->getTool($websiteId)->load();
            $this->platformStorageConfig->save($config, $websiteId);
        }
        return $config;
    }
}
