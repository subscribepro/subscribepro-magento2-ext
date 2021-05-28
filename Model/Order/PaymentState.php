<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Order;

use Swarming\SubscribePro\Api\Data\OrderPaymentStateInterface;

class PaymentState extends \Magento\Framework\DataObject implements OrderPaymentStateInterface
{
    /**
     * @return string|null
     */
    public function getState()
    {
        return $this->_getData(self::KEY_STATE);
    }

    /**
     * @param string $state
     * @return void
     */
    public function setState($state)
    {
        $this->setData(self::KEY_STATE, $state);
    }

    /**
     * @return string|null
     */
    public function getToken()
    {
        return $this->_getData(self::KEY_TOKEN);
    }

    /**
     * @param string $token
     * @return void
     */
    public function setToken($token)
    {
        $this->setData(self::KEY_TOKEN, $token);
    }

    /**
     * @return mixed[]|null
     */
    public function getGatewaySpecificFields()
    {
        return $this->_getData(self::GATEWAY_SPECIFIC_FIELDS)
            ? (array)$this->_getData(self::GATEWAY_SPECIFIC_FIELDS)
            : null;
    }

    /**
     * @param mixed[] $gatewaySpecificFields
     * @return void
     */
    public function setGatewaySpecificFields($gatewaySpecificFields)
    {
        $this->setData(self::GATEWAY_SPECIFIC_FIELDS, $gatewaySpecificFields);
    }
}
