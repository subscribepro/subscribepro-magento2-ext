<?php

namespace Swarming\SubscribePro\Model\Config;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Platform extends General
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->directoryList = $directoryList;
        parent::__construct($scopeConfig);
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getClientId($websiteCode = null)
    {
        return $this->scopeConfig->getValue(
            'swarming_subscribepro/platform/client_id',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null|bool $websiteCode
     * @return string
     */
    public function getClientSecret($websiteCode = null)
    {
        $scopeType = $websiteCode === false ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('swarming_subscribepro/platform/client_secret', $scopeType, $websiteCode);
    }

    /**
     * @param string|null $websiteCode
     * @return bool
     */
    public function isLogEnabled($websiteCode = null)
    {
        return $this->scopeConfig->isSetFlag(
            'swarming_subscribepro/platform/log_enabled',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getLogFilename($websiteCode = null)
    {
        $fileName = $this->scopeConfig->getValue(
            'swarming_subscribepro/platform/log_filename',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
        $varDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        return $varDir . DIRECTORY_SEPARATOR . ltrim($fileName, DIRECTORY_SEPARATOR);
    }
}
