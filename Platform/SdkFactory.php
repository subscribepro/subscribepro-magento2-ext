<?php

namespace Swarming\SubscribePro\Platform;

use SubscribePro\Sdk;

class SdkFactory
{
    /**
     * @param array $data
     * @return \SubscribePro\Sdk
     */
    public function create(array $data = array())
    {
        $config = isset($data['config']) && is_array($data['config'])
            ? $data['config']
            : [];
        return new Sdk($config);
    }
}
