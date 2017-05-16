<?php

namespace Swarming\SubscribePro\Block\Adminhtml\Order\Create\Billing\Method;

class Form extends \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form
{

    /**
     * Retrieve available payment methods
     *
     * @return array
     */
    public function getMethods()
    {
        $methods = parent::getMethods();
        return $methods;
    }

}