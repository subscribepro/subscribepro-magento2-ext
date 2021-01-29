<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Cart;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
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

    public function __construct(
        Context $context,
        Quote $quote,
        DirectoryHelper $directoryHelper,
        PaymentRequestConfig $paymentRequestConfig,
        JsonSerializer $jsonJsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quote = $quote;
        $this->directoryHelper = $directoryHelper;
        $this->paymentRequestConfig = $paymentRequestConfig;
        $this->jsonJsonSerializer = $jsonJsonSerializer;
    }

    public function getMerchantDomainName(): string
    {
        return 'qhive-vpn.qbees.tech';
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

    public function getCreateSessionUrl(): string
    {
//        return rtrim($this->getApiBaseUrl(), '/') . '/services/v2/vault/applepay/create-session.json';
        return 'https://api.subscribepro.com/services/v2/vault/applepay/create-session.json';
    }

    public function getApplePayPaymentRequest()
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

    public function getPaymentAuthorizedUrl()
    {
        return $this->_urlBuilder->getUrl('subscribepro/applepay/paymentauthorized');
    }
}
