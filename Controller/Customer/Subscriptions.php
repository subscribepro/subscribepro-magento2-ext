<?php

namespace Swarming\SubscribePro\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;

class Subscriptions extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Swarming\SubscribePro\Model\Config\General $generalConfig
    ) {
        $this->generalConfig = $generalConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->generalConfig->isEnabled()) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $resultForward->forward('noroute');
        }

        /** @var \Magento\Framework\View\Result\Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set(__('My Product Subscriptions'));

        return $page;
    }
}
