<?php

namespace Swarming\SubscribePro\Block\Checkout\Onepage\Success;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Swarming\SubscribePro\Model\Config\General;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;

class Subscriptions extends Template
{
    /**
     * @var Session
     */
    protected Session $checkoutSession;

    /**
     * @var General
     */
    protected General $generalConfig;

    /**
     * @param Context $context
     * @param General $generalConfig
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        General $generalConfig,
        Session $checkoutSession,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->generalConfig = $generalConfig;
        parent::__construct($context, $data);
    }

    /**
     * @return int
     */
    public function getCountSubscriptions(): int
    {
        $createdSubscriptionIds = $this->checkoutSession->getData(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS);
        return !empty($createdSubscriptionIds) ? count($createdSubscriptionIds) : 0;
    }

    /**
     * @return bool
     */
    public function hasFailedSubscriptions(): bool
    {
        return (bool)$this->checkoutSession->getData(SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT);
    }

    /**
     * @return void
     */
    public function clearSubscriptionSessionData()
    {
        /* @phpstan-ignore-next-line */
        $this->checkoutSession->setData(SubscriptionCreator::CREATED_SUBSCRIPTION_IDS, []);
        /* @phpstan-ignore-next-line */
        $this->checkoutSession->setData(SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT, 0);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->generalConfig->isEnabled()) {
            return parent::_toHtml();
        }
        return '';
    }
}
