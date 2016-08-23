<?php

namespace Swarming\SubscribePro\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;

class Subscriptions extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set(__('My product subscriptions'));

        return $page;
    }
}
