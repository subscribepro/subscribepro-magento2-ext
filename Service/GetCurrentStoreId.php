<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;

class GetCurrentStoreId
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Swarming\SubscribePro\Model\Config\ScopeDefiner
     */
    private $scopeDefiner;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Swarming\SubscribePro\Model\Config\ScopeDefiner $scopeDefiner
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Swarming\SubscribePro\Model\Config\ScopeDefiner $scopeDefiner,
        \Magento\Framework\App\State $appState
    ) {
        $this->storeManager = $storeManager;
        $this->scopeDefiner = $scopeDefiner;
        $this->appState = $appState;
    }

    /**
     * @return int
     */
    public function execute(): int
    {
        return $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_FRONTEND
            ? (int)$this->storeManager->getStore()->getId()
            : $this->getStoreForAdminConfiguration();
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    private function getStoreForAdminConfiguration(): int
    {
        $scopeScore = $this->scopeDefiner->getScope();
        switch ($scopeScore) {
            case StoreScopeInterface::SCOPE_STORE:
                $storeId = (int)$this->scopeDefiner->getScopeValue();
                break;
            case StoreScopeInterface::SCOPE_WEBSITE:
                /** @var Website $currentWebsite */
                $currentWebsite = $this->storeManager->getWebsite($this->scopeDefiner->getScopeValue());
                $storeId = (int)$currentWebsite->getDefaultStore()->getId();
                break;
            case ScopeConfigInterface::SCOPE_TYPE_DEFAULT:
                $storeId = Store::DEFAULT_STORE_ID;
                break;
            default:
                throw new \UnexpectedValueException(sprintf('%s unsupported scope score', $scopeScore));
        }

        return $storeId;
    }
}
