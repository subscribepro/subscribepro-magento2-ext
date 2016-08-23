<?php

namespace Swarming\SubscribePro\Block\Checkout\Onepage\Success;

use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;

class Subscriptions extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $configGeneral;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Swarming\SubscribePro\Model\Config\General $configGeneral,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->configGeneral = $configGeneral;
        parent::__construct($context, $data);
    }

    /**
     * @return int
     */
    public function getCountSubscriptions()
    {
        return count($this->checkoutSession->getData(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS));
    }

    /**
     * @return bool
     */
    public function hasFailedSubscriptions()
    {
        return (bool)$this->checkoutSession->getData(SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->configGeneral->isEnabled()) {
            return parent::_toHtml();
        }
        return '';
    }
}
