<?php

namespace Swarming\SubscribePro\Platform\Tool;

/**
 * @method \SubscribePro\Tools\Config getTool($websiteCode = null)
 */
class Config extends AbstractTool
{
    /**
     * @param string|null $key
     * @param string $websiteCode
     * @return array|string
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getConfig($key = null, $websiteCode = null)
    {
        $config = $this->getTool($websiteCode)->load();
        return null === $key
            ? $config
            : (isset($config[$key]) ? $config[$key] : null);
    }
}
