<?php

namespace Swarming\SubscribePro\Platform\Tool;

/**
 * @method \SubscribePro\Tools\Config getTool($websiteId = null)
 */
class Config extends AbstractTool
{
    /**
     * @param string|null $key
     * @param int $websiteId
     * @return array|string
     * @throws \SubscribePro\Exception\HttpException
     */
    public function getConfig($key = null, $websiteId = null)
    {
        $config = $this->getTool($websiteId)->load();
        return null === $key
            ? $config
            : (isset($config[$key]) ? $config[$key] : null);
    }
}
