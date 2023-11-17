<?php

namespace Swarming\SubscribePro\Platform\Tool;

/**
 * @method \SubscribePro\Tools\Config getTool($websiteId = null)
 */
class Oauth extends AbstractTool
{
    /**
     * @param string|int|null $customerId
     * @param string|int|null $websiteId
     *
     * @return mixed
     */
    public function getWidgetAccessTokenByCustomerId($customerId, $websiteId = null)
    {
        return $this->getTool($websiteId)->retrieveWidgetAccessTokenByCustomerId($customerId);
    }

    /**
     * @param string|int|null $websiteId
     * @return mixed
     */
    public function getWidgetAccessTokenByGuest($websiteId = null)
    {
        return $this->getTool($websiteId)->retrieveAccessToken([
            'scope' => 'widget',
        ]);
    }

    /**
     * @param string|int|null $websiteId
     * @return mixed
     */
    public function getSessionAccessToken($websiteId = null): mixed
    {
        return $this->getTool($websiteId)->retrieveAccessToken([
            'scope' => 'session',
            'grant_type' => 'client_credentials',
        ]);
    }
}
