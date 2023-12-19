<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Cart;

use Magento\Checkout\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;
use Swarming\SubscribePro\Helper\QuoteItem;
use Swarming\SubscribePro\Model\ApplePay\PaymentRequestConfig;

class ApplePay extends Template
{
    /**
     * @var Quote
     */
    private $quote;
    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;
    /**
     * @var PaymentRequestConfig
     */
    private $paymentRequestConfig;
    /**
     * @var JsonSerializer
     */
    private $jsonJsonSerializer;
    /**
     * @var ApplePayConfigProvider
     */
    private $applePayConfigProvider;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var QuoteItem
     */
    private $quoteItemHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * ApplePay constructor.
     *
     * @param Context $context
     * @param Quote $quote
     * @param DirectoryHelper $directoryHelper
     * @param PaymentRequestConfig $paymentRequestConfig
     * @param ApplePayConfigProvider $applePayConfigProvider
     * @param JsonSerializer $jsonJsonSerializer
     * @param Session $checkoutSession
     * @param QuoteItem $quoteItemHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Quote $quote,
        DirectoryHelper $directoryHelper,
        PaymentRequestConfig $paymentRequestConfig,
        ApplePayConfigProvider $applePayConfigProvider,
        JsonSerializer $jsonJsonSerializer,
        Session $checkoutSession,
        QuoteItem $quoteItemHelper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quote = $quote;
        $this->directoryHelper = $directoryHelper;
        $this->paymentRequestConfig = $paymentRequestConfig;
        $this->applePayConfigProvider = $applePayConfigProvider;
        $this->jsonJsonSerializer = $jsonJsonSerializer;
        $this->checkoutSession = $checkoutSession;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @return false|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function toHtml()
    {
        if (!$this->isMethodAvailable()) {
            return false;
        }
        return parent::_toHtml();
    }

    /**
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function isMethodAvailable()
    {
        $quote = $this->checkoutSession->getQuote();
        $isLoggedIn = $this->customerSession->isLoggedIn();
        $isActiveNonSubscription = $this->applePayConfigProvider->isActiveNonSubscription($quote->getStoreId());

        if ($isActiveNonSubscription && !$isLoggedIn) {
            if (count($quote->getItems())) {
                foreach ($quote->getItems() as $item) {
                    if ($this->quoteItemHelper->hasSubscription($item)) {
                        return false;
                    }
                }
            }
        } elseif (!$isLoggedIn) {
            return false;
        } elseif ($isLoggedIn) {
            if (count($quote->getItems()) && !$isActiveNonSubscription) {
                $atLeastOneRegular = false;
                foreach ($quote->getItems() as $item) {
                    if ($this->quoteItemHelper->hasSubscription($item)) {
                        // if have at least one RegularProduct - allow ApplePay button;
                        $atLeastOneRegular = true;
                    }
                }
                if (!$atLeastOneRegular) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getMerchantDomainName(): string
    {
        $storeId = $this->quote->getStoreId();
        return $this->applePayConfigProvider->getDomain($storeId);
    }

    /**
     * Get token from SubscriberPro
     *
     * @return string
     */
    public function getApiAccessToken(): string
    {
        return $this->paymentRequestConfig->getAccessToken();
    }

    /**
     * @return string
     */
    public function getCreateSessionUrl(): string
    {
        $websiteId = $this->quote->getStore()->getWebsiteId();
        return rtrim($this->applePayConfigProvider->getApiBaseUrl($websiteId), '/')
            . '/services/v2/vault/applepay/create-session.json';
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getApplePayPaymentRequest(): string
    {
        $paymentRequestConfig = $this->paymentRequestConfig->getRequestConfig();

        if (!$paymentRequestConfig) {
            return '';
        }

        return $this->jsonJsonSerializer->serialize($paymentRequestConfig);
    }

    /**
     * @return string
     */
    public function getShippingSelectedUrl(): string
    {
        return $this->_urlBuilder->getUrl('subscribepro/applepay/shippinglist');
    }

    /**
     * @return string
     */
    public function onShippingMethodSelected(): string
    {
        return $this->_urlBuilder->getUrl('subscribepro/applepay/shippingmethod');
    }

    /**
     * @return string
     */
    public function getPaymentAuthorizedUrl(): string
    {
        return $this->_urlBuilder->getUrl('subscribepro/applepay/paymentauthorized');
    }
}
