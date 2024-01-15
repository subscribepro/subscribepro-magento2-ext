<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\Convert\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformManagerCustomer;
use Swarming\SubscribePro\Platform\Tool\Oauth as PlatformOAuth;

class PaymentRequestConfig extends DataObject
{
    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;
    /**
     * @var CartInterface|Quote
     */
    private $quote;
    /**
     * @var SessionManagerInterface
     */
    private $checkoutSession;
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

    /**
     * PaymentRequestConfig constructor.
     *
     * @param SessionManagerInterface $checkoutSession
     * @param DirectoryHelper $directoryHelper
     * @param Currency $currency
     * @param PlatformOAuth $platformOAuth
     * @param PlatformManagerCustomer $platformManagerCustomer
     * @param LoggerInterface $logger
     */
    public function __construct(
        SessionManagerInterface $checkoutSession,
        DirectoryHelper $directoryHelper,
        Currency $currency,
        PlatformOAuth $platformOAuth,
        PlatformManagerCustomer $platformManagerCustomer,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->directoryHelper = $directoryHelper;
        $this->currency = $currency;
        $this->platformOAuth = $platformOAuth;
        $this->platformManagerCustomer = $platformManagerCustomer;
        $this->logger = $logger;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRequestConfig(): array
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        // Req fields
        if ($quote->isVirtual()) {
            $requiredShippingContactFields = ['name', 'email'];
        } else {
            $requiredShippingContactFields = ['name', 'email', 'postalAddress'];
        }

        return [
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
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getMerchantCountryCode(): string
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        return $this->directoryHelper->getDefaultCountry($quote->getStore());
    }

    /**
     * @return CartInterface|Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote(): CartInterface|Quote
    {
        if (!$this->quote) {
            /** @var CheckoutSession $session */
            $session = $this->checkoutSession;
            $this->quote = $session->getQuote();
        }

        return $this->quote;
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getMerchantCurrencyCode(): string
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        return $quote->getBaseCurrencyCode();
    }

    /**
     * Retrieve the shipping rates for the Apple Pay session
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getApplePayShippingMethods(): array
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        // Pull out the shipping rates
        $shippingRates = $shippingAddress
            ->collectShippingRates()
            ->getGroupedAllShippingRates();

        $rates = [];
        $currentRate = false;

        if (!$shippingRates) {
            $this->logger->error('QuoteId: ' . $quote->getId());
            $this->logger->error('No  shippingRates');
            return $rates;
        }

        foreach ($shippingRates as $carrier => $groupRates) {
            foreach ($groupRates as $shippingRate) {
                // Is this the current selected shipping method?
                if ($quote->getShippingAddress()->getShippingMethod() == $shippingRate->getCode()) {
                    $currentRate = $this->convertShippingRate($shippingRate);
                } else {
                    $rates[] = $this->convertShippingRate($shippingRate);
                }
            }
        }

        // Add the current shipping rate first
        if ($currentRate) {
            array_unshift($rates, $currentRate);
        }

        return $rates;
    }

    /**
     * Convert a shipping rate into Apple Pay format
     *
     * @param \Magento\Quote\Model\Quote\Address\Rate $shippingRate
     * @return array
     */
    public function convertShippingRate(\Magento\Quote\Model\Quote\Address\Rate $shippingRate)
    {
        // Don't show the same information twice
        $detail = $shippingRate->getMethodTitle();
        if ($shippingRate->getCarrierTitle() == $detail || $detail == 'Free') {
            $detail = '';
        }

        return [
            'label' => $shippingRate->getCarrierTitle(),
            'amount' => (float) $this->formatPrice($shippingRate->getPrice()),
            'detail' => $detail,
            'identifier' => $shippingRate->getCode(),
        ];
    }

    /**
     * @return array[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getApplePayLineItems(): array
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        return [
            [
                'label' => 'SUBTOTAL',
                'amount' => $this->formatPrice($quote->getShippingAddress()->getSubtotalWithDiscount()),
            ],
            [
                'label' => 'SHIPPING',
                'amount' => $this->formatPrice($quote->getShippingAddress()->getShippingAmount()),
            ],
            [
                'label' => 'TAX',
                'amount' => $this->formatPrice($quote->getShippingAddress()->getTaxAmount()),
            ],
        ];
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getApplePayTotal(): array
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        return [
            'label' => 'MERCHANT',
            'amount' => $this->formatPrice($quote->getGrandTotal()),
        ];
    }

    /**
     * @return array
     */
    public function getSupportedApplePayCardTypes(): array
    {
        return [
            'visa',
            'masterCard',
            'discover',
            'amex',
        ];
    }

    /**
     * @param float|null $price
     * @return string
     */
    public function formatPrice($price): string
    {
        return $this->currency->format(
            $price,
            ['display'=>\Magento\Framework\Currency\Data\Currency::NO_SYMBOL],
            false
        );
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAccessToken()
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        $customerId = $quote->getCustomerId();
        $websiteId = $quote->getStore()->getWebsiteId();
        if ($customerId) {
            try {
                $subscriberProCustomerId = $this->platformManagerCustomer->getCustomerById(
                    $customerId,
                    true,
                    $websiteId
                )->getId();
            } catch (NoSuchEntityException $e) {
                $this->logger->error($e->getMessage());
                $subscriberProCustomerId = false;
            }
        }

        try {
            if ($customerId) {
                $data = $this->platformOAuth->getWidgetAccessTokenByCustomerId($subscriberProCustomerId, $websiteId);
            } else {
                $data = $this->platformOAuth->getWidgetAccessTokenByGuest($websiteId);
            }

            return ($data && isset($data['access_token'])) ? $data['access_token'] : '';
        } catch (LocalizedException $e) {
            $this->logger->error('QuoteId: ' . $quote->getId());
            $this->logger->error('WebsiteId: ' . $websiteId);
            if ($customerId && $subscriberProCustomerId) {
                $this->logger->error('SubscriberProCustomerId: ' . $subscriberProCustomerId);
            }
            $this->logger->error($e->getMessage());
        }

        return '';
    }
}
