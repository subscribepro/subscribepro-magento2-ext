<?php

namespace Swarming\SubscribePro\Ui\ConfigProvider;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;
use Magento\Checkout\Model\ConfigProviderInterface;

final class Checkout implements ConfigProviderInterface
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\ConfigProvider
     */
    protected $gatewayConfigProvider;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider
    ) {
        $this->gatewayConfigProvider = $gatewayConfigProvider;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                GatewayConfigProvider::CODE => $this->gatewayConfigProvider->getConfig()
            ]
        ];
    }
}
