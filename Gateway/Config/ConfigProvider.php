<?php

namespace Swarming\SubscribePro\Gateway\Config;

use SubscribePro\Tools\Config as PlatformConfig;

class ConfigProvider
{
    const CODE = 'subscribe_pro';

    const VAULT_CODE = 'subscribe_pro_vault';

    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \Magento\Payment\Model\CcConfig
     */
    protected $ccConfig;

    /**
     * @var \Magento\Payment\Model\CcConfigProvider
     */
    protected $ccConfigProvider;

    /**
     * @var \Swarming\SubscribePro\Platform\Tool\Config
     */
    protected $platformConfigTool;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     * @param \Magento\Payment\Model\CcConfig $ccConfig
     * @param \Magento\Payment\Model\CcConfigProvider $ccConfigProvider
     * @param \Swarming\SubscribePro\Platform\Tool\Config $platformConfigTool
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Magento\Payment\Model\CcConfig $ccConfig,
        \Magento\Payment\Model\CcConfigProvider $ccConfigProvider,
        \Swarming\SubscribePro\Platform\Tool\Config $platformConfigTool
    ) {
        $this->generalConfig = $generalConfig;
        $this->gatewayConfig = $gatewayConfig;
        $this->ccConfig = $ccConfig;
        $this->ccConfigProvider = $ccConfigProvider;
        $this->platformConfigTool = $platformConfigTool;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $config = [
            'vaultCode' => self::VAULT_CODE,
            'isActive' => false,
        ];

        if ($this->generalConfig->isEnabled()) {
            $config = [
                'vaultCode' => self::VAULT_CODE,
                'isActive' => $this->gatewayConfig->isActive(),
                'environmentKey' => $this->platformConfigTool->getConfig(PlatformConfig::CONFIG_TRANSPARENT_REDIRECT_ENVIRONMENT_KEY),
                'availableCardTypes' => $this->getCcAvailableTypes(),
                'ccTypesMapper' => $this->gatewayConfig->getCcTypesMapper(),
                'hasVerification' => $this->gatewayConfig->hasVerification(),
                'cvvImageUrl' => $this->ccConfig->getCvvImageUrl(),
                'icons' => $this->ccConfigProvider->getIcons()
            ];
        }
        return $config;
    }

    /**
     * @return array
     */
    protected function getCcAvailableTypes()
    {
        $types = $this->ccConfig->getCcAvailableTypes();
        $availableTypes = $this->gatewayConfig->getAvailableCardTypes();
        return $availableTypes ? array_intersect_key($types, array_flip($availableTypes)) : $types;
    }
}
