<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Config;

use Magento\Store\Model\ScopeInterface;

class OrderCallback
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string|null $websiteCode
     * @return string
     */
    public function getSharedSecret(string $websiteCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            'swarming_subscribepro/order_callback/shared_secret',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteCode
        );
    }
}
