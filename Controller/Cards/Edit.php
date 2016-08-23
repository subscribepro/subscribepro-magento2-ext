<?php

namespace Swarming\SubscribePro\Controller\Cards;

use Magento\Framework\Controller\ResultFactory;

class Edit extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
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
