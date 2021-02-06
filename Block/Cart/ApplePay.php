<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Cart;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use Swarming\SubscribePro\Model\ApplePay\PaymentRequestConfig;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;

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

    public function __construct(
        Context $context,
        Quote $quote,
        DirectoryHelper $directoryHelper,
        PaymentRequestConfig $paymentRequestConfig,
        ApplePayConfigProvider $applePayConfigProvider,
        JsonSerializer $jsonJsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quote = $quote;
        $this->directoryHelper = $directoryHelper;
        $this->paymentRequestConfig = $paymentRequestConfig;
        $this->applePayConfigProvider = $applePayConfigProvider;
        $this->jsonJsonSerializer = $jsonJsonSerializer;
    }

    /**
     * @param null $storeId
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

    public function getApplePayPaymentRequest(): string
    {
        $paymentRequestConfig = $this->paymentRequestConfig->getRequestConfig();

        if (!$paymentRequestConfig) {
            return '';
        }

        return $this->jsonJsonSerializer->serialize($paymentRequestConfig);
    }

    public function getShippingSelectedUrl(): string
    {
        return $this->_urlBuilder->getUrl('subscribepro/applepay/shippinglist');
    }

    public function onShippingMethodSelected(): string
    {
        return $this->_urlBuilder->getUrl('subscribepro/applepay/shippingmethod');
    }

    public function getPaymentAuthorizedUrl(): string
    {
        return $this->_urlBuilder->getUrl('subscribepro/applepay/paymentauthorized');
    }
}
