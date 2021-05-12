<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Api\Data;

interface OrderPaymentStateInterface
{
    const KEY_STATE = 'state';
    const KEY_TOKEN = 'token';

    /**
     * @return string|null
     */
    public function getState();

    /**
     * @param string $state
     * @return void
     */
    public function setState($state);

    /**
     * @return string|null
     */
    public function getToken();

    /**
     * @param string $token
     * @return void
     */
    public function setToken($token);
}
