<?php

namespace Swarming\SubscribePro\Platform;

class Platform
{
    /**
     * @var \SubscribePro\SdkFactory
     */
    protected $sdkFactory;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Platform
     */
    protected $configPlatform;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \SubscribePro\Sdk[]
     */
    protected $sdkByWebsiteCode = [];

    /**
     * @var string
     */
    protected $currentWebsiteCode;

    /**
     * @param \SubscribePro\SdkFactory $sdkFactory
     * @param \Swarming\SubscribePro\Model\Config\Platform $configPlatform
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $config
     */
    public function __construct(
        \SubscribePro\SdkFactory $sdkFactory,
        \Swarming\SubscribePro\Model\Config\Platform $configPlatform,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $config = []
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->configPlatform = $configPlatform;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int|null $websiteId
     * @return \SubscribePro\Sdk
     */
    public function getSdk($websiteId = null)
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        if (empty($this->sdkByWebsiteCode[$websiteCode])) {
            $this->sdkByWebsiteCode[$websiteCode] = $this->createSdk($websiteCode);
        }
        return $this->sdkByWebsiteCode[$websiteCode];
    }

    /**
     * @param string $websiteCode
     * @return \SubscribePro\Sdk
     */
    private function createSdk($websiteCode)
    {
        $platformConfig = [
            'client_id' => $this->configPlatform->getClientId($websiteCode),
            'client_secret' => $this->configPlatform->getClientSecret($websiteCode),
            'logging_enable' => $this->configPlatform->isLogEnabled($websiteCode),
            'logging_level' => $this->configPlatform->getLogLevel($websiteCode),
            'logging_file_name' => $this->configPlatform->getLogFilename($websiteCode)
        ];
        return $this->sdkFactory->create(['config' => array_merge($this->config, $platformConfig)]);
    }
}
