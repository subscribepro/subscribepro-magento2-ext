<?php

namespace Swarming\SubscribePro\Gateway\Config;

use SubscribePro\Tools\Config as PlatformConfig;
use Swarming\SubscribePro\Platform\Tool\Oauth;

class ConfigProvider
{
    public const CODE = 'subscribe_pro';

    public const VAULT_CODE = 'subscribe_pro_vault';

    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var Config
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Oauth
     */
    protected $oauth;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param Config $gatewayConfig
     * @param \Magento\Payment\Model\CcConfig $ccConfig
     * @param \Magento\Payment\Model\CcConfigProvider $ccConfigProvider
     * @param \Swarming\SubscribePro\Platform\Tool\Config $platformConfigTool
     * @param \Swarming\SubscribePro\Platform\Tool\Oauth $oauth
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        Config                                      $gatewayConfig,
        \Magento\Payment\Model\CcConfig             $ccConfig,
        \Magento\Payment\Model\CcConfigProvider     $ccConfigProvider,
        \Swarming\SubscribePro\Platform\Tool\Config $platformConfigTool,
        \Swarming\SubscribePro\Platform\Tool\Oauth  $oauth,
        \Magento\Store\Model\StoreManagerInterface  $storeManager
    ) {
        $this->generalConfig = $generalConfig;
        $this->gatewayConfig = $gatewayConfig;
        $this->ccConfig = $ccConfig;
        $this->ccConfigProvider = $ccConfigProvider;
        $this->platformConfigTool = $platformConfigTool;
        $this->storeManager = $storeManager;
        $this->oauth = $oauth;
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    public function getConfig($storeId = null)
    {
        $config = [
            'vaultCode' => self::VAULT_CODE,
            'isActive' => false,
        ];

        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        if ($this->isEnabledPayment($websiteId)) {
            $environmentKey = $this->platformConfigTool->getConfig(
                PlatformConfig::CONFIG_TRANSPARENT_REDIRECT_ENVIRONMENT_KEY,
                $websiteId
            );
            $config = [
                'apiBaseUrl' => $this->generalConfig->getBaseUrl() ? $this->generalConfig->getBaseUrl() . '/' : '',
                'vaultCode' => self::VAULT_CODE,
                'isActive' => $this->gatewayConfig->isActive($storeId),
                'isThreeDSActive' => $this->gatewayConfig->isThreeDSActive($storeId),
                'sessionAccessToken' => $this->oauth->getSessionAccessToken($storeId),
                'browserSize' => $this->gatewayConfig->getBrowserSize($storeId),
                'acceptHeader' => $this->gatewayConfig->getAcceptHeader($storeId),
                'environmentKey' => $environmentKey,
                Config::KEY_WALLET_AUTHORIZATION_AMOUNT => $this->gatewayConfig->getWalletAuthorizationAmount($storeId),
                'availableCardTypes' => $this->getCcAvailableTypes($storeId),
                'ccTypesMapper' => $this->gatewayConfig->getCcTypesMapper($storeId),
                'hasVerification' => $this->gatewayConfig->hasVerification($storeId),
                'cvvImageUrl' => $this->ccConfig->getCvvImageUrl(),
                'icons' => $this->ccConfigProvider->getIcons()
            ];
        }
        return $config;
    }

    /**
     * Is Subscribe Pro payment method enabled for website.
     *
     * @param string|null $websiteId
     * @return bool
     */
    public function isEnabledPayment($websiteId)
    {
        return $this->generalConfig->isEnabled($websiteId);
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    protected function getCcAvailableTypes($storeId = null)
    {
        $types = $this->ccConfig->getCcAvailableTypes();
        $availableTypes = $this->gatewayConfig->getAvailableCardTypes($storeId);
        return $availableTypes ? array_intersect_key($types, array_flip($availableTypes)) : $types;
    }
}
