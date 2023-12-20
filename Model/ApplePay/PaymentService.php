<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\Region as DirectoryRegion;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\Quote\Model\ResourceModel\Quote\Payment as QuotePaymentResourceModel;
use Psr\Log\LoggerInterface;
use SubscribePro\Service\Token\TokenInterface;
use Swarming\SubscribePro\Helper\ApplePay\Vault as ApplePayVaultHelper;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformCustomer;
use Swarming\SubscribePro\Platform\Service\ApplePay\PaymentProfile as PlatformApplePayPaymentProfile;

class PaymentService
{
    public const DEFAULT_PHONE_NUMBER = '0000000000';

    /**
     * @var Quote
     */
    protected $quote;
    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface|null
     */
    protected $customerData;
    /**
     * @var ApplePayVaultHelper
     */
    private $applePayVaultHelper;
    /**
     * @var SessionManagerInterface
     */
    private $checkoutSession;
    /**
     * @var CustomerSession
     */
    private $customerSession;
    /**
     * @var Currency
     */
    private $currency;
    /**
     * @var DirectoryRegion
     */
    private $directoryRegion;
    /**
     * @var PlatformCustomer
     */
    private $platformCustomer;
    /**
     * @var PlatformApplePayPaymentProfile
     */
    private $platformPaymentProfile;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    /**
     * @var QuotePaymentResourceModel
     */
    private $quotePaymentResourceModel;
    /**
     * @var QuoteResourceModel
     */
    private $quoteResourceModel;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Construct the payment service.
     *
     * @param SessionManagerInterface        $checkoutSession
     * @param CustomerSession                $customerSession
     * @param Currency                       $currency
     * @param DirectoryRegion                $directoryRegion
     * @param PlatformCustomer               $platformCustomer
     * @param PlatformApplePayPaymentProfile $platformPaymentProfile
     * @param OrderService                   $orderService
     * @param QuoteManagement                $quoteManagement
     * @param QuotePaymentResourceModel      $quotePaymentResourceModel
     * @param QuoteResourceModel             $quoteResourceModel
     * @param JsonSerializer                 $jsonSerializer
     * @param ApplePayVaultHelper            $vault
     * @param LoggerInterface                $logger
     */
    public function __construct(
        SessionManagerInterface $checkoutSession,
        CustomerSession $customerSession,
        Currency $currency,
        DirectoryRegion $directoryRegion,
        PlatformCustomer $platformCustomer,
        PlatformApplePayPaymentProfile $platformPaymentProfile,
        OrderService $orderService,
        QuoteManagement $quoteManagement,
        QuotePaymentResourceModel $quotePaymentResourceModel,
        QuoteResourceModel $quoteResourceModel,
        JsonSerializer $jsonSerializer,
        ApplePayVaultHelper $vault,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->currency = $currency;
        $this->directoryRegion = $directoryRegion;
        $this->platformCustomer = $platformCustomer;
        $this->platformPaymentProfile = $platformPaymentProfile;
        $this->orderService = $orderService;
        $this->quoteManagement = $quoteManagement;
        $this->quotePaymentResourceModel = $quotePaymentResourceModel;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->jsonSerializer = $jsonSerializer;
        $this->applePayVaultHelper = $vault;
        $this->logger = $logger;
    }

    /**
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getQuote()
    {
        if (!$this->quote) {
            /** @var Session $checkoutSession */
            $checkoutSession = $this->checkoutSession;
            $this->quote = $checkoutSession->getQuote();
        }

        return $this->quote;
    }

    /**
     * @inheritdoc
     */
    protected function getCustomerSession()
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

    /**
     * @inheritdoc
     */
    public function getDirectoryRegionByName($administrativeArea, $countryId)
    {
        return $this->directoryRegion->loadByName($administrativeArea, $countryId);
    }

    /**
     * @inheritdoc
     */
    public function getDirectoryRegionByCode($administrativeArea, $countryId)
    {
        return $this->directoryRegion->loadByCode($administrativeArea, $countryId);
    }

    /**
     * @param string $customerEmail
     * @param false  $createIfNotExist
     * @param null   $websiteId
     *
     * @return \SubscribePro\Service\Customer\CustomerInterface
     */
    public function getPlatformCustomer(string $customerEmail, $createIfNotExist = false, $websiteId = null)
    {
        return $this->platformCustomer->getCustomer($customerEmail, $createIfNotExist, $websiteId);
    }

    /**
     * @param int                                           $subscribeProCustomerId
     * @param array                                         $paymentProfileData
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @param null                                          $websiteId
     *
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws LocalizedException
     */
    public function createPlatformPaymentProfile(
        int $subscribeProCustomerId,
        array $paymentProfileData,
        $billingAddress,
        $websiteId = null
    ) {
        // New payment profile
        $paymentProfile = $this->platformPaymentProfile->createApplePayProfile($paymentProfileData, $websiteId);

        if ($billingAddress) {
            $spBillingAddress = $paymentProfile->getBillingAddress();
            $this->mapMagentoAddressToPlatform($billingAddress, $spBillingAddress);
            $paymentProfile->setBillingAddress($spBillingAddress);
        } else {
            throw new LocalizedException(__('The billing address is empty.'));
        }

        // Set SP customer id
        $paymentProfile->setCustomerId($subscribeProCustomerId);
        // Update payment profile with post data
        $paymentProfile->setApplePayPaymentData($paymentProfileData);

        // Create and save profile via API
        $this->platformPaymentProfile->saveApplePayProfile($paymentProfile);

        return $paymentProfile;
    }

    /**
     * @param AddressInterface $magentoAddress
     * @param $platformAddress
     * @return void
     */
    protected function mapMagentoAddressToPlatform(AddressInterface $magentoAddress, $platformAddress)
    {
        /** @var QuoteAddress $magentoAddress */
        $platformAddress->setFirstName($magentoAddress->getData('firstname'));
        $platformAddress->setLastName($magentoAddress->getData('lastname'));
        $platformAddress->setCompany($magentoAddress->getData('company'));
        $platformAddress->setStreet1((string) $magentoAddress->getStreetLine(1));
        if (strlen($magentoAddress->getStreetLine(2))) {
            $platformAddress->setStreet2((string) $magentoAddress->getStreetLine(2));
        } else {
            $platformAddress->setStreet2(null);
        }
        if (strlen($magentoAddress->getStreetLine(3))) {
            $platformAddress->setStreet3((string) $magentoAddress->getStreetLine(3));
        } else {
            $platformAddress->setStreet3(null);
        }
        $platformAddress->setCity($magentoAddress->getData('city'));
        $platformAddress->setRegion($magentoAddress->getRegionCode());
        $platformAddress->setPostcode($magentoAddress->getData('postcode'));
        $platformAddress->setCountry($magentoAddress->getData('country_id'));
        $platformAddress->setPhone($magentoAddress->getData('telephone'));
    }

    /**
     * @param $type
     * @param $throwExceptionOnTypeNotFound
     * @return string|null
     * @throws LocalizedException
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
                    __('Invalid credit card type: %type', ['type' => $type])
                );
            }
        }

        return null;
    }

    /**
     * @return array
     */
    protected function getAllCardTypeMappings(): array
    {
        // Subscribe Pro / Payment Fields type => Magento type
        $cardTypes = [
            'visa' => 'VI',
            'master' => 'MC',
            'american_express' => 'AE',
            'discover' => 'DI',
            'jcb' => 'JCB',
        ];

        return $cardTypes;
    }

    /**
     * @param array $paymentData
     * @return int|mixed
     * @throws LocalizedException
     */
    public function setPaymentToQuote(array $paymentData)
    {
        if (!$paymentData
            || !isset($paymentData['token']['paymentData'])
            || !is_array($paymentData['token']['paymentData'])
        ) {
            throw new LocalizedException(__('Apple Pay payment data not found!'));
        }

        // Quote
        $quote = $this->getQuote();

        // Set customer details
        if ($this->getCustomerSession()->isLoggedIn()) {
            $quote->setCustomer($this->getCustomerData());
        } else {
            // Save email for guests
            if (!isset($paymentData['shippingContact']['emailAddress'])) {
                throw new LocalizedException(__('Email address missing from Apple Pay payment details!'));
            }
            $quote->setCustomerEmail($paymentData['shippingContact']['emailAddress']);
            // Save name
            if (!isset($paymentData['shippingContact']['givenName'])
                || !isset($paymentData['shippingContact']['familyName'])
            ) {
                throw new LocalizedException(__('Customer name missing from Apple Pay payment details!'));
            }
            $quote->setCustomerFirstname($paymentData['shippingContact']['givenName']);
            $quote->setCustomerLastname($paymentData['shippingContact']['familyName']);
        }

        $billingAddress = $this->convertToMagentoAddress($paymentData['billingContact']);
        // Save billing address
        $quote->getBillingAddress()->addData($billingAddress);

        $shippingAddress = $this->convertToMagentoAddress($paymentData['shippingContact']);
        // Save shipping address
        if (!$quote->isVirtual()) {
            $quote->getShippingAddress()->addData($shippingAddress);
        }

        // Save payment details on quote
        if ($this->getCustomerSession()->isLoggedIn()) {
            $this->createPaymentProfileForCustomer($paymentData, $quote->getBillingAddress());
        } else {
            $this->createPaymentToken($paymentData);
        }
        return $quote->getId();
    }

    /**
     * @param string|array|null $address
     * @return array
     */
    protected function convertToMagentoAddress($address)
    {
        if (is_string($address)) {
            $address = $this->jsonSerializer->unserialize($address);
        }

        // Retrieve the countryId from the request
        $countryId = strtoupper($address['countryCode']);
        $magentoAddress = [
            'street' => implode("\n", $address['addressLines']),
            'firstname' => $address['givenName'],
            'lastname' => $address['familyName'],
            'city' => $address['locality'],
            'country_id' => $countryId,
            'postcode' => $address['postalCode'],
            'telephone' => (isset($address['phoneNumber']) ? $address['phoneNumber'] : self::DEFAULT_PHONE_NUMBER)
        ];

        // Determine if a region is required for the selected country
        if (isset($address['administrativeArea'])) {
            // Lookup region
            $regionModel = $this->getDirectoryRegionByCode($address['administrativeArea'], $countryId);
            if (!$regionModel) {
                $this->getDirectoryRegionByName($address['administrativeArea'], $countryId);
            } else {
                $magentoAddress['region_id'] = $regionModel->getId();
                $magentoAddress['region'] = $regionModel->getName();
            }
        }

        return $magentoAddress;
    }

    /**
     * @param array                                    $applePayPayment
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function createPaymentProfileForCustomer(array $applePayPayment, $billingAddress)
    {
        $quote = $this->getQuote();
        $websiteId = $quote->getStore()->getWebsiteId();

        // Create SP customer
        $platformCustomer = $this->getPlatformCustomer($quote->getCustomerEmail(), true, $websiteId);

        $paymentProfile = $this->createPlatformPaymentProfile(
            (int) $platformCustomer->getId(),
            $applePayPayment['token']['paymentData'],
            $billingAddress,
            $websiteId
        );

        // Set apple pay pay method on quote
        $payment = $quote->getPayment();
        $payment->setMethod(\Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider::CODE);
        // Clear out additional information that may have been set previously in the session
        $payment->setAdditionalInformation([]);
        $payment->setAdditionalInformation('save_card', false);
        $payment->setAdditionalInformation('is_new_card', false);
        $payment->setAdditionalInformation('payment_token', $paymentProfile->getPaymentToken());
        $payment->setAdditionalInformation('payment_profile_id', $paymentProfile->getId());
        $payment->setAdditionalInformation('is_third_party', false);
        $payment->setAdditionalInformation('subscribe_pro_order_token', '');
        // CC Number
        $ccNumber = $paymentProfile->getCreditcardFirstDigits() . 'XXXXXX' . $paymentProfile->getCreditcardLastDigits();
        $payment->setAdditionalInformation('obscured_cc_number', $ccNumber);
        $payment->setData('payment_method_token', $paymentProfile->getPaymentToken());
        $payment->setData('is_active_payment_token_enabler', $this->getCustomerSession()->isLoggedIn());
        $payment->setData('cc_number', $ccNumber);
        $payment->setCcNumberEnc($payment->encrypt($ccNumber));
        $payment->setData('cc_exp_month', $paymentProfile->getCreditcardMonth());
        $payment->setData('cc_exp_year', $paymentProfile->getCreditcardYear());
        $payment->setData('cc_type', $this->mapSubscribeProCardTypeToMagento($paymentProfile->getCreditcardType()));
        $quote->setPayment($payment);

        $this->quotePaymentResourceModel->save($payment);
        $this->quoteResourceModel->save($quote);

        return $this;
    }

    /**
     * @param array $applePayPayment
     * @return $this
     * @throws LocalizedException
     */
    public function createPaymentToken(array $applePayPayment)
    {
        $quote = $this->getQuote();
        $paymentMethod = $this->createApplePayPaymentVaultToken(
            $quote->getBillingAddress(),
            $applePayPayment['token']['paymentData']
        );

        // Set apple pay pay method on quote
        $payment = $quote->getPayment();

        $payment->setMethod(\Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider::CODE);
        // Clear out additional information that may have been set previously in the session
        $payment->setAdditionalInformation([]);
        $payment->setAdditionalInformation('save_card', false);
        $payment->setAdditionalInformation('is_new_card', true);
        $payment->setAdditionalInformation('payment_token', $paymentMethod->getToken());
        $payment->setAdditionalInformation('is_third_party', false);
        $payment->setAdditionalInformation('subscribe_pro_order_token', '');
        // CC Number
        $ccNumber = $paymentMethod->getFirstSixDigits() . 'XXXXXX' . $paymentMethod->getLastFourDigits();
        $payment->setData('payment_method_token', $paymentMethod->getToken());
        $payment->setAdditionalInformation('obscured_cc_number', $ccNumber);
        $payment->setData('cc_number', $ccNumber);
        $payment->setCcNumberEnc($payment->encrypt($ccNumber));
        $payment->setData('cc_exp_month', $paymentMethod->getMonth());
        $payment->setData('cc_exp_year', $paymentMethod->getYear());
        $payment->setData('cc_type', $this->mapSubscribeProCardTypeToMagento($paymentMethod->getCardType()));
        $quote->setPayment($payment);
        $quote->setCheckoutMethod('guest');

        // Save quote
        $this->quotePaymentResourceModel->save($payment);
        $this->quoteResourceModel->save($quote);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function placeOrder($quoteId, $defaultShippingMethod = null): bool
    {
        return $this->orderService->createOrder($quoteId, $defaultShippingMethod);
    }

    /**
     * @param AddressInterface $billingAddress
     * @param array $applePayPaymentData
     * @return TokenInterface
     */
    protected function createApplePayPaymentVaultToken(
        AddressInterface $billingAddress,
        array $applePayPaymentData
    ): TokenInterface {
        return $this->applePayVaultHelper->createApplePayPaymentToken($billingAddress, $applePayPaymentData);
    }
}
