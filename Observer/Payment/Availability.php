<?php

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Gateway\Config\Config;

class Availability implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Helper\Quote $quoteHelper
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Payment\Model\Method\Adapter $methodInstance */
        $methodInstance = $observer->getData('method_instance');

        /** @var \Magento\Framework\DataObject $result */
        $result = $observer->getData('result');

        /** @var \Magento\Quote\Api\Data\CartInterface $quote */
        $quote = $observer->getData('quote');
        $quote = $quote ?: $this->checkoutSession->getQuote();
        if (!$quote) {
            return;
        }

        $methodCode = $methodInstance->getCode();
        $isAvailable = $result->getData('is_available');
        $isActiveNonSubscription = $methodInstance->getConfigData(Config::KEY_ACTIVE_NON_SUBSCRIPTION);

        if ($this->quoteHelper->hasSubscription($quote)) {
            $isAvailable = ConfigProvider::CODE == $methodCode && $isAvailable;
        } elseif (ConfigProvider::CODE == $methodCode && !$isActiveNonSubscription) {
            $isAvailable = false;
        }

        $result->setData('is_available', $isAvailable);
    }
}
