<?php

namespace Swarming\SubscribePro\Platform\Tool;

abstract class AbstractTool extends \Swarming\SubscribePro\Platform\AbstractGeneral
{
    /**
     * @param int|null $websiteId
     * @return \SubscribePro\Tools\AbstractTool
     */
    protected function getTool($websiteId = null)
    {
        return $this->getSdk($websiteId)->getTool($this->name);
    }
}
