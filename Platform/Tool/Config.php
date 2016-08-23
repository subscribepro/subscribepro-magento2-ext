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
    protected $configStorage;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param \Swarming\SubscribePro\Platform\Storage\Config $configStorage
     * @param string $name
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform,
        \Swarming\SubscribePro\Platform\Storage\Config $configStorage,
        $name
    ) {
        parent::__construct($platform, $name);
        $this->configStorage = $configStorage;
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
        $config = $this->configStorage->load($websiteId);
        if (!$config) {
            $config = $this->getTool($websiteId)->load();
            $this->configStorage->save($config, $websiteId);
        }
        return $config;
    }
}
