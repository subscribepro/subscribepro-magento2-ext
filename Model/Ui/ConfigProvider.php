<?php

namespace Swarming\SubscribePro\Model\Ui;

use SubscribePro\Tools\Config as PlatformConfig;
use Magento\Checkout\Model\ConfigProviderInterface;

final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'subscribe_pro';

    const VAULT_CODE = 'subscribe_pro_vault';

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Config
     */
    protected $platformConfig;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $config
     * @param \Swarming\SubscribePro\Platform\Helper\Config $platformConfig
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $config,
        \Swarming\SubscribePro\Platform\Helper\Config $platformConfig
    ) {
        $this->config = $config;
        $this->platformConfig = $platformConfig;
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
                    'vaultCode' => self::VAULT_CODE,
                    'isActive' => $this->config->isActive(),
                    'environmentKey' => $this->platformConfig->getConfig(PlatformConfig::CONFIG_TRANSPARENT_REDIRECT_ENVIRONMENT_KEY),
                    'availableCardTypes' => $this->config->getAvailableCardTypes(),
                    'ccTypesMapper' => $this->config->getCcTypesMapper()
                ]
            ]
        ];
    }
}
