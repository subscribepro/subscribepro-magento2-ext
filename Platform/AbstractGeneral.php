<?php

namespace Swarming\SubscribePro\Platform;

abstract class AbstractGeneral
{
    /**
     * @var \Swarming\SubscribePro\Platform\Platform
     */
    protected $platform;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param string $name
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform,
        $name
    ) {
        $this->platform = $platform;
        $this->name = $name;
    }

    /**
     * @param int $websiteId
     * @return \SubscribePro\Sdk
     */
    protected function getSdk($websiteId = null)
    {
        return $this->platform->getSdk($websiteId);
    }
}
