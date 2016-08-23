<?php

namespace Swarming\SubscribePro\Controller\Cards;

use Magento\Framework\Controller\ResultFactory;

class NewAction extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\VaultConfig
     */
    protected $spVaultConfig;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Swarming\SubscribePro\Gateway\Config\VaultConfig $spVaultConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Swarming\SubscribePro\Gateway\Config\VaultConfig $spVaultConfig
    ) {
        $this->spVaultConfig = $spVaultConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Forward
     */
    public function execute()
    {
        if (!$this->spVaultConfig->isActive()) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $resultForward->forward('noroute');
        }

        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}
