<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\Convert\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformManagerCustomer;
use Swarming\SubscribePro\Platform\Tool\Oauth as PlatformOAuth;
Use Psr\Log\LoggerInterface;

class PaymentRequestConfig extends DataObject
{
    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;
    private $quote;
    /**
     * @var CheckoutSession|SessionManagerInterface
     */
    private $checkoutSession;
    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;
    /**
     * @var Currency
     */
    private $currency;
    /**
     * @var PlatformOAuth
     */
    private $platformOAuth;
    /**
     * @var PlatformManagerCustomer
     */
    private $platformManagerCustomer;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SessionManagerInterface $checkoutSession,
        DirectoryHelper $directoryHelper,
        Currency $currency,
        CheckoutHelper $checkoutHelper,
        PlatformOAuth $platformOAuth,
        PlatformManagerCustomer $platformManagerCustomer,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->directoryHelper = $directoryHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->currency = $currency;
        $this->platformOAuth = $platformOAuth;
        $this->platformManagerCustomer = $platformManagerCustomer;
        $this->logger = $logger;
    }

    public function getRequestConfig(): array
    {
        // Req fields
        if ($this->getQuote()->isVirtual()) {
            $requiredShippingContactFields = ['name', 'email'];
        } else {
            $requiredShippingContactFields = ['name', 'email', 'postalAddress'];
        }

        $data = [
            'countryCode' => $this->getMerchantCountryCode(),
            'currencyCode' => $this->getMerchantCurrencyCode(),
            'shippingMethods' => $this->getApplePayShippingMethods(),
            'lineItems' => $this->getApplePayLineItems(),
            'total' => $this->getApplePayTotal(),
            'supportedNetworks' => $this->getSupportedApplePayCardTypes(),
            'merchantCapabilities' => ['supports3DS'],
            'requiredShippingContactFields' => $requiredShippingContactFields,
            'requiredBillingContactFields' => ['name', 'postalAddress'],
        ];

        return $data;
    }

    /**
     * @return string|null
     */
    public function getMerchantCountryCode(): string
    {
        return $this->directoryHelper->getDefaultCountry($this->getQuote()->getStore());
    }

    public function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    public function getMerchantCurrencyCode(): string
    {
        return $this->getQuote()->getBaseCurrencyCode();
    }

    /**
     * Retrieve the shipping rates for the Apple Pay session
     *
     * @return array
     */
    public function getApplePayShippingMethods(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getApplePayLineItems(): array
    {
        return [
            [
                'label' => 'SUBTOTAL',
                'amount' => $this->formatPrice($this->getQuote()->getShippingAddress()->getSubtotalWithDiscount()),
            ],
            [
                'label' => 'SHIPPING',
                'amount' => $this->formatPrice($this->getQuote()->getShippingAddress()->getShippingAmount()),
            ],
            [
                'label' => 'TAX',
                'amount' => $this->formatPrice($this->getQuote()->getShippingAddress()->getTaxAmount()),
            ],
        ];
    }

    /**
     * @return array
     */
    public function getApplePayTotal(): array
    {
        return [
            'label' => 'MERCHANT',
            'amount' => $this->formatPrice($this->getQuote()->getGrandTotal()),
        ];
    }

    public function getSupportedApplePayCardTypes(): array
    {
        return [
            'visa',
            'masterCard',
            'discover',
            'amex',
        ];
    }

    public function formatPrice($price)
    {
        return $this->currency->format($price, ['display'=>\Zend_Currency::NO_SYMBOL], false);
    }

    public function getAccessToken()
    {
        $quote = $this->checkoutSession->getQuote();
        $customerId = $quote->getCustomerId();
        $websiteId = $quote->getStore()->getWebsiteId();

        try {
            $subscriberProCustomerId = $this->platformManagerCustomer->getCustomerById(
                $customerId,
                true,
                $websiteId
            )->getId();
        } catch (NoSuchEntityException $e) {
            var_dump($e->getMessage());
            die;
            $subscriberProCustomerId = false;
        }

        try {
            $data = $this->platformOAuth->getWidgetAccessTokenByCustomerId($subscriberProCustomerId, $websiteId);

            return ($data && isset($data['access_token'])) ? $data['access_token'] : '';
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
        }

        return '';
    }
}
