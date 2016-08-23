<?php

namespace Swarming\SubscribePro\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'subscribe_pro';

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $config;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $config
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $config
    ) {
        $this->config = $config;
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
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                ]
            ]
        ];
    }
}
