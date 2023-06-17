<?php

namespace Swarming\SubscribePro\Block\Vault;

class AddButton extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\VaultConfig
     */
    protected $platformVaultConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Swarming\SubscribePro\Gateway\Config\VaultConfig $spVaultConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Swarming\SubscribePro\Gateway\Config\VaultConfig $spVaultConfig,
        array $data = []
    ) {
        $this->platformVaultConfig = $spVaultConfig;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->platformVaultConfig->getValue('active')) {
            return parent::_toHtml();
        }
        return '';
    }
}
