<?php

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\Method\Free;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;
use Swarming\SubscribePro\Gateway\Config\Config;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Helper\Quote;
use Swarming\SubscribePro\Helper\ThirdPartyPayment;

class Availability implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Quote
     */
    protected $quoteHelper;

    /**
     * @var ThirdPartyPayment
     */
    private $thirdPartyPayment;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Session $checkoutSession
     * @param Quote $quoteHelper
     * @param ThirdPartyPayment $thirdPartyPayment
     * @param LoggerInterface $logger
     */
    public function __construct(
        Session                  $checkoutSession,
        Quote                    $quoteHelper,
        ThirdPartyPayment        $thirdPartyPayment,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteHelper = $quoteHelper;
        $this->thirdPartyPayment = $thirdPartyPayment;
        $this->logger = $logger;
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

            // For a subscription order, we filter out all payment methods
            // except the Subscribe Pro and (sometimes) free methods
            if ($this->quoteHelper->hasSubscription($quote)) {
                $this->logger->debug('SS PRO: Availability: methodCode: ' . $methodCode);
                switch ($methodCode) {
                    case Free::PAYMENT_METHOD_FREE_CODE:
                        $isAvailable = $this->quoteHelper->isRecurringQuote($quote);
                        break;
                    case ApplePayConfigProvider::CODE:
                    case ConfigProvider::CODE:
                        $isAvailable = true;
                        break;
                    default:
                        $isAvailable = $this->thirdPartyPayment->isThirdPartyPaymentMethodAllowed(
                            $methodCode,
                            (int)$quote->getStoreId()
                        );
                        $this->logger->debug('SS PRO: Availability: isAvailable: ' . $isAvailable);
                        break;
                }
            } elseif (ConfigProvider::CODE === $methodCode && !$isActiveNonSubscription) {
                $isAvailable = false;
            }

            $result->setData('is_available', $isAvailable);
        }
    }
}
