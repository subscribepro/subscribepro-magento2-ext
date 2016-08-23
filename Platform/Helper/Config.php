<?php

namespace Swarming\SubscribePro\Platform\Helper;

class Config
{
    /**
     * @var \SubscribePro\Tools\Config
     */
    protected $sdkConfigTool;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform
    ) {
        $this->sdkConfigTool = $platform->getSdk()->getConfigTool();
    }

    /**
     * @param string|null $key
     * @return array|string
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getConfig($key = null)
    {
        $config = $this->sdkConfigTool->load();
        return null === $key
            ? $config
            : (isset($config[$key]) ? $config[$key] : null);
    }
}
