<?php

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\Method\Free;
use Swarming\SubscribePro\Gateway\Config\Config;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;

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
     * @var \Swarming\SubscribePro\Model\Config\ThirdPartyPayment
     */
    private $thirdPartyPaymentConfig;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Helper\Quote $quoteHelper
     * @param \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper,
        \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteHelper = $quoteHelper;
        $this->thirdPartyPaymentConfig = $thirdPartyPaymentConfig;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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

        if ($isAvailable) {
            $isActiveNonSubscription = $methodInstance->getConfigData(Config::KEY_ACTIVE_NON_SUBSCRIPTION);

            // For a subscription order, we filter out all payment methods except the Subscribe Pro and (sometimes) free methods
            if ($this->quoteHelper->hasSubscription($quote)) {
                switch ($methodCode) {
                    case Free::PAYMENT_METHOD_FREE_CODE:
                        $isAvailable = $this->quoteHelper->isRecurringQuote($quote);
                        break;
                    case ApplePayConfigProvider::CODE:
                    case ConfigProvider::CODE:
                        $isAvailable = true;
                        break;
                    default:
                        $isAvailable = $this->isThirdPartyPaymentMethodAllowed($methodCode, (int)$quote->getStoreId());
                        break;
                }
            } elseif (ConfigProvider::CODE === $methodCode && !$isActiveNonSubscription) {
                $isAvailable = false;
            }

            $result->setData('is_available', $isAvailable);
        }
    }

    /**
     * @param string $methodCode
     * @param int $storeId
     * @return bool
     */
    private function isThirdPartyPaymentMethodAllowed(string $methodCode, int $storeId): bool
    {
        return $methodCode === $this->thirdPartyPaymentConfig->getAllowedMethod($storeId);
    }
}
