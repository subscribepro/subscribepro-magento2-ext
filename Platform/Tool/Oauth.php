<?php

namespace Swarming\SubscribePro\Platform\Tool;

/**
 * @method \SubscribePro\Tools\Config getTool($websiteId = null)
 */
class Oauth extends AbstractTool
{
    /**
     * @param      $customerId
     * @param null $websiteId
     * @return mixed
     */
    public function getWidgetAccessTokenByCustomerId($customerId, $websiteId = null)
    {
        return $this->getTool($websiteId)->retrieveWidgetAccessTokenByCustomerId($customerId);
    }

    /**
     * @param null $websiteId
     * @return mixed
     */
    public function getWidgetAccessTokenByGuest($websiteId = null)
    {
        return $this->getTool($websiteId)->retrieveAccessToken([
            'scope' => 'widget',
        ]);
    }
}
