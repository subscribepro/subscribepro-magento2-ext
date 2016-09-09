<?php

namespace Swarming\SubscribePro\Controller\Cards;

use Magento\Framework\Controller\ResultFactory;

class Edit extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\VaultConfig
     */
    protected $platformVaultConfig;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Swarming\SubscribePro\Gateway\Config\VaultConfig $spVaultConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Swarming\SubscribePro\Gateway\Config\VaultConfig $spVaultConfig
    ) {
        $this->platformVaultConfig = $spVaultConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Forward
     */
    public function execute()
    {
        if (!$this->platformVaultConfig->isActive()) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $resultForward->forward('noroute');
        }

        /** @var \Magento\Framework\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $page->getConfig()->getTitle()->set(__('Edit Credit Card'));

        /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
        $navigationBlock = $page->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('vault/cards/listaction');
        }
        return $page;
    }
}
