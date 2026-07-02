<?php

namespace Swarming\SubscribePro\Platform;

class Platform
{
    /**
     * @var \Swarming\SubscribePro\Platform\SdkFactory
     */
    protected $sdkFactory;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Platform
     */
    protected $platformConfig;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Advanced
     */
    protected $advancedConfig;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string
     */
    protected $defaultWebsiteId;

    /**
     * @var \SubscribePro\Sdk[]
     */
    protected $sdkByWebsiteCode = [];

    /**
     * @param \Swarming\SubscribePro\Platform\SdkFactory $sdkFactory
     * @param \Swarming\SubscribePro\Model\Config\Platform $platformConfig
     * @param \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $config
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\SdkFactory $sdkFactory,
        \Swarming\SubscribePro\Model\Config\Platform $platformConfig,
        \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $config = []
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->platformConfig = $platformConfig;
        $this->advancedConfig = $advancedConfig;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @param $websiteId
     * @return void
     */
    public function setDefaultWebsite($websiteId)
    {
        $this->defaultWebsiteId = $websiteId;
    }

    /**
     * @param int|null $websiteId
     * @return \SubscribePro\Sdk
     */
    public function getSdk($websiteId = null)
    {
        $websiteCode = $this->getWebsiteCode($websiteId);
        if (empty($this->sdkByWebsiteCode[$websiteCode])) {
            $this->sdkByWebsiteCode[$websiteCode] = $this->createSdk($websiteCode);
        }
        return $this->sdkByWebsiteCode[$websiteCode];
    }

    /**
     * @param int|null $websiteId
     * @return string
     */
    protected function getWebsiteCode($websiteId = null)
    {
        $websiteId = null !== $websiteId ? $websiteId : $this->defaultWebsiteId;
        return $this->storeManager->getWebsite($websiteId)->getCode();
    }

    /**
     * @param string $websiteCode
     * @return \SubscribePro\Sdk
     */
    private function createSdk($websiteCode)
    {
        $platformConfig = [
            'base_url' => $this->platformConfig->getBaseUrl($websiteCode),
            'client_id' => $this->platformConfig->getClientId($websiteCode),
            'client_secret' => $this->platformConfig->getClientSecret($websiteCode),
            'logging_enable' => $this->platformConfig->isLogEnabled($websiteCode),
            'logging_file_name' => $this->platformConfig->getLogFilename($websiteCode)
        ];

        // Frontend-safe timeouts: only override the SDK defaults when a positive value is
        // configured, so a blank/zero request timeout falls back to the SDK default (30s).
        $apiRequestTimeout = $this->advancedConfig->getApiRequestTimeout($websiteCode);
        if ($apiRequestTimeout > 0) {
            $platformConfig['api_request_timeout'] = $apiRequestTimeout;
        }
        $connectTimeout = $this->advancedConfig->getApiConnectTimeout($websiteCode);
        if ($connectTimeout > 0) {
            $platformConfig['connect_timeout'] = $connectTimeout;
        }

        return $this->sdkFactory->create(['config' => array_merge($this->config, $platformConfig)]);
    }
}
