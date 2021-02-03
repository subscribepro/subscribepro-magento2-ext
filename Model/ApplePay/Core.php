<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\Region as DirectoryRegion;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformCustomer;
use Swarming\SubscribePro\Platform\Service\ApplePay\PaymentProfile as PlatformApplePayPaymentProfile;

abstract class Core
{
    /**
     * @var Quote
     */
    protected $quote;
    protected $customerData;
    /**
     * @var SessionManagerInterface
     */
    protected $checkoutSession;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var Currency
     */
    protected $currency;
    /**
     * @var DirectoryRegion
     */
    protected $directoryRegion;
    /**
     * @var PlatformCustomer
     */
    protected $platformCustomer;
    /**
     * @var PlatformApplePayPaymentProfile
     */
    protected $platformPaymentProfile;
    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        SessionManagerInterface $checkoutSession,
        CustomerSession $customerSession,
        Currency $currency,
        DirectoryRegion $directoryRegion,
        PlatformCustomer $platformCustomer,
        PlatformApplePayPaymentProfile $platformPaymentProfile,
        QuoteManagement  $quoteManagement,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->currency = $currency;
        $this->directoryRegion = $directoryRegion;
        $this->platformCustomer = $platformCustomer;
        $this->platformPaymentProfile = $platformPaymentProfile;
        $this->quoteManagement = $quoteManagement;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|Quote
     */
    public function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    public function formatPrice($price)
    {
        return $this->currency->format($price, ['display'=>\Zend_Currency::NO_SYMBOL], false);
    }

    public function getDirectoryRegionByName($administrativeArea, $countryId)
    {
        return $this->directoryRegion->loadByName($administrativeArea, $countryId);
    }

    public function getDirectoryRegionByCode($administrativeArea, $countryId)
    {
        return $this->directoryRegion->loadByCode($administrativeArea, $countryId);
    }

    /**
     * @return array
     */
    public function getGrandTotal()
    {
        return [
            'label' => 'MERCHANT',
            'amount' => $this->formatPrice($this->getQuote()->getGrandTotal()),
        ];
    }

    /**
     * @return array
     */
    public function getRowItems(): array
    {
        $address = $this->getQuote()->getShippingAddress();
        return [
            [
                'label' => 'SUBTOTAL',
                'amount' => $this->formatPrice($address->getSubtotalWithDiscount()),
            ],
            [
                'label' => 'SHIPPING',
                'amount' => $this->formatPrice($address->getShippingAmount()),
            ],
            [
                'label' => 'TAX',
                'amount' => $this->formatPrice($address->getTaxAmount()),
            ],
        ];
    }

    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerData()
    {
        if (null === $this->customerData) {
            $this->customerData = $this->getCustomerSession()->getCustomerData();
        }

        return $this->customerData;
    }

    public function getPlatformCustomer(string $customerEmail, $createIfNotExist = false, $websiteId = null)
    {
        return $this->platformCustomer->getCustomer($customerEmail, $createIfNotExist, $websiteId);
    }

    public function createPlatformPaymentProfile(
        $subscribeProCustomerId,
        array $paymentProfileData,
        $customer = null,
        $billingAddress = null,
        $websiteId = null
    ) {
        // New payment profile
        $paymentProfile = $this->platformPaymentProfile->createApplePayProfile($paymentProfileData, $websiteId);

        if ($customer) {
            $paymentProfile = $this->initProfileWithCustomerDefault($paymentProfile, $customer);
        }
        if ($billingAddress) {
            $spBillingAddress = $paymentProfile->getBillingAddress();
//            $this->mapMagentoAddressToPlatform($billingAddress, $spBillingAddress);
            $paymentProfile->setBillingAddress($spBillingAddress);
        }

        // Set SP customer id
        $paymentProfile->setCustomerId($subscribeProCustomerId);
        // Update payment profile with post data
        $paymentProfile->setApplePayPaymentData($paymentProfileData);

        // Create and save profile via API
        $this->platformPaymentProfile->saveProfile($paymentProfile);

        return $paymentProfile;
    }

    public function initProfileWithCustomerDefault($paymentProfile, $customer)
    {
        if (!$customer) {
            // TODO: maybe it will require to load through repository not from session.
            $customer = $this->getCustomerSession()->getCustomer();
        }
        // Grab billing address
        $addressId = $customer->getData('default_billing');

        // Add address data if default billing addy exists
        if ($addressId) {
            // Get address
            // TODO:  it may contain data not from ApplePay.
            $billingAddress = $customer->getDefaultBillingAddress();
            // Map
            $this->mapMagentoAddressToPlatform($billingAddress, $paymentProfile->getBillingAddress());
        } else {
            // Empty billing address
            $paymentProfile->getBillingAddress()->setFirstName($customer->getData('firstname'));
            $paymentProfile->getBillingAddress()->setLastName($customer->getData('lastname'));
        }

        return $paymentProfile;
    }

    /**
     * @param $magentoAddress
     * @param \SubscribePro\Service\Address\AddressInterface $platformAddress
     */
    protected function mapMagentoAddressToPlatform($magentoAddress, $platformAddress)
    {
        $platformAddress->setFirstName($magentoAddress->getData('firstname'));
        $platformAddress->setLastName($magentoAddress->getData('lastname'));
        $platformAddress->setCompany($magentoAddress->getData('company'));
        $platformAddress->setStreet1((string) $magentoAddress->getStreetLine(1));
        if (strlen($magentoAddress->getStreetLine(2))) {
            $platformAddress->setStreet2((string) $magentoAddress->getStreetLine(2));
        } else {
            $platformAddress->setStreet2(null);
        }
        $platformAddress->setCity($magentoAddress->getData('city'));
        $platformAddress->setRegion($magentoAddress->getRegionCode());
        $platformAddress->setPostcode($magentoAddress->getData('postcode'));
        $platformAddress->setCountry($magentoAddress->getData('country_id'));
        $platformAddress->setPhone($magentoAddress->getData('telephone'));
    }

    /**
     * @param $type
     * @param bool $throwExceptionOnTypeNotFound
     * @return mixed|null
     */
    public function mapSubscribeProCardTypeToMagento($type, $throwExceptionOnTypeNotFound = true)
    {
        // Map of card types
        $cardTypes = $this->getAllCardTypeMappings();

        if (isset($cardTypes[$type])) {
            return $cardTypes[$type];
        } else {
            if ($throwExceptionOnTypeNotFound) {
                throw new LocalizedException(
                    new Phrase('Invalid credit card type: %type', ['type' => $type])
                );
            }
        }

        return null;
    }

    /**
     * @return array
     */
    protected function getAllCardTypeMappings()
    {
        // Subscribe Pro / Spreedly type => Magento type
        $cardTypes = [
            'visa' => 'VI',
            'master' => 'MC',
            'american_express' => 'AE',
            'discover' => 'DI',
            'jcb' => 'JCB',
        ];

        return $cardTypes;
    }

    public function quoteSubmitOrder($cartId, PaymentInterface $paymentMethod = null)
    {
        return $this->quoteManagement->placeOrder($cartId, $paymentMethod);
    }
}
