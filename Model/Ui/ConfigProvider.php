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
     * @var \Magento\Payment\Model\CcConfig
     */
    protected $ccConfig;

    /**
     * @var \Magento\Payment\Model\CcConfigProvider
     */
    protected $ccConfigProvider;

    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Config
     */
    protected $platformConfig;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $config
     * @param \Magento\Payment\Model\CcConfig $ccConfig
     * @param \Magento\Payment\Model\CcConfigProvider $ccConfigProvider
     * @param \Swarming\SubscribePro\Platform\Helper\Config $platformConfig
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $config,
        \Magento\Payment\Model\CcConfig $ccConfig,
        \Magento\Payment\Model\CcConfigProvider $ccConfigProvider,
        \Swarming\SubscribePro\Platform\Helper\Config $platformConfig
    ) {
        $this->config = $config;
        $this->ccConfig = $ccConfig;
        $this->ccConfigProvider = $ccConfigProvider;
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
                self::CODE => $this->getPaymentConfig()
            ]
        ];
    }

    /**
     * @return array
     */
    public function getPaymentConfig()
    {
        return [
            'vaultCode' => self::VAULT_CODE,
            'isActive' => $this->config->isActive(),
            'environmentKey' => $this->platformConfig->getConfig(PlatformConfig::CONFIG_TRANSPARENT_REDIRECT_ENVIRONMENT_KEY),
            'availableCardTypes' => $this->getCcAvailableTypes(),
            'ccTypesMapper' => $this->config->getCcTypesMapper(),
            'hasVerification' => $this->config->hasVerification(),
            'cvvImageUrl' => $this->ccConfig->getCvvImageUrl(),
            'icons' => $this->ccConfigProvider->getIcons()
        ];
    }

    /**
     * @return array
     */
    protected function getCcAvailableTypes()
    {
        $types = $this->ccConfig->getCcAvailableTypes();
        $availableTypes = $this->config->getAvailableCardTypes();
        if ($availableTypes) {
            foreach (array_keys($types) as $code) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                }
            }
        }
        return $types;
    }
}
