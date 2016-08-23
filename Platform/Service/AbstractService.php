<?php

namespace Swarming\SubscribePro\Platform\Service;

abstract class AbstractService extends \Swarming\SubscribePro\Platform\AbstractGeneral
{
    /**
     * @param int|null $websiteId
     * @return \SubscribePro\Service\AbstractService
     */
    protected function getService($websiteId = null)
    {
        return $this->getSdk($websiteId)->getService($this->name);
    }
}
