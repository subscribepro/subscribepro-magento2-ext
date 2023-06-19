<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Api\Data;

interface OrderPaymentStateInterface
{
    public const KEY_STATE = 'state';
    public const KEY_TOKEN = 'token';
    public const GATEWAY_SPECIFIC_FIELDS = 'gateway_specific_fields';

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

    /**
     * @return mixed[]|null
     */
    public function getGatewaySpecificFields();

    /**
     * @param mixed[] $gatewaySpecificFields
     * @return void
     */
    public function setGatewaySpecificFields($gatewaySpecificFields);
}
