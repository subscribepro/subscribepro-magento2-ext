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
use Magento\Quote\Model\QuoteManagement;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Helper\Vault;
use Swarming\SubscribePro\Model\ApplePay\Core as ApplePayCore;
use Swarming\SubscribePro\Platform\Manager\Customer as PlatformCustomer;
use Swarming\SubscribePro\Platform\Service\ApplePay\PaymentProfile as PlatformApplePayPaymentProfile;

class PaymentService extends ApplePayCore
{
    const DEFAULT_PHONE_NUMBER = '0000000000';
    /**
     * @var Vault
     */
    private $vault;

    /**
     * PaymentService constructor.
     *
     * @param SessionManagerInterface $checkoutSession
     * @param CustomerSession $customerSession
     * @param Currency $currency
     * @param DirectoryRegion $directoryRegion
     * @param PlatformCustomer $platformCustomer
     * @param PlatformApplePayPaymentProfile $platformPaymentProfile
     * @param OrderService $orderService
     * @param QuoteManagement $quoteManagement
     * @param JsonSerializer $jsonSerializer
     * @param LoggerInterface $logger
     * @param Vault $vault
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
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger,
        Vault $vault
    ) {
        $this->vault = $vault;
        parent::__construct($checkoutSession,
            $customerSession,
            $currency,
            $directoryRegion,
            $platformCustomer,
            $platformPaymentProfile,
            $orderService,
            $quoteManagement,
            $jsonSerializer,
            $logger
        );
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
     * @param $address
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
     * @param array $applePayPayment
     * @param       $billingAddress
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

//        // Recalculate quote
        // TODO: remove deprecated call.
        $payment->save();
        $quote->save();

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
        $paymentMethod = $this->vault->createApplePayPaymentToken(
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
        $payment->save();
        $quote->save();

        return $this;
    }
}
