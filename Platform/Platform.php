<?php

namespace Swarming\SubscribePro\Platform;

class Platform
{
    /**
     * @var \SubscribePro\Sdk
     */
    protected $sdk;

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
     * @param \SubscribePro\SdkFactory $sdkFactory
     * @param \Swarming\SubscribePro\Model\Config\Platform $configPlatform
     * @param array $config
     */
    public function __construct(
        \SubscribePro\SdkFactory $sdkFactory,
        \Swarming\SubscribePro\Model\Config\Platform $configPlatform,
        array $config = []
    ) {
        $this->sdkFactory = $sdkFactory;
        $this->configPlatform = $configPlatform;
        $this->config = $config;
    }

    /**
     * @return \SubscribePro\Sdk
     */
    public function getSdk()
    {
        if (null === $this->sdk) {
            $this->initSdk();
        }
        return $this->sdk;
    }

    protected function initSdk()
    {
        $platformConfig = [
            'client_id' => $this->configPlatform->getClientId(),
            'client_secret' => $this->configPlatform->getClientSecret(),
            'logging_enable' => $this->configPlatform->isLogEnabled(),
            'logging_level' => $this->configPlatform->getLogLevel(),
            'logging_file_name' => $this->configPlatform->getLogFilename()
        ];
        $this->sdk = $this->sdkFactory->create(['config' => array_merge($this->config, $platformConfig)]);
    }
}
