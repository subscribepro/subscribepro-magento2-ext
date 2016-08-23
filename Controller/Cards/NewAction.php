<?php

namespace Swarming\SubscribePro\Controller\Cards;

use Magento\Framework\Controller\ResultFactory;

class NewAction extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @return \Magento\Framework\Controller\Result\Forward
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}
