<?php

namespace Swarming\SubscribePro\Platform\Tool;

/**
 * @method \SubscribePro\Tools\Config getTool($websiteId = null)
 */
class Oauth extends AbstractTool
{
    public function getWidgetAccessTokenByCustomerId($customerId, $websiteId = null)
    {
        return $this->getTool($websiteId)->retrieveWidgetAccessTokenByCustomerId($customerId);
    }

    public function getWidgetAccessTokenByGuest($websiteId = null)
    {
        return $this->getTool($websiteId)->retrieveWidgetAccessTokenByGuest();
    }
}
