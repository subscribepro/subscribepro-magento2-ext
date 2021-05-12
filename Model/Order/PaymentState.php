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
}
