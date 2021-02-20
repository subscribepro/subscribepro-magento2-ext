<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Swarming\SubscribePro\Model\ApplePay\Core as ApplePayCore;

class PaymentService extends ApplePayCore
{
    public function setPaymentToQuote(array $paymentData)
    {
        if (!$paymentData
            || !isset($paymentData['token']['paymentData'])
            || !is_array($paymentData['token']['paymentData'])
        ) {
            throw new LocalizedException(new Phrase('Apple Pay payment data not found!'));
        }

        // Quote
        $quote = $this->getQuote();

        // Set customer details
        if ($this->getCustomerSession()->isLoggedIn()) {
            $quote->setCustomer($this->getCustomerData());
        } else {
            var_dump('--- STOP ---');
            die;
            // Save email for guests
            if (!isset($paymentData['shippingContact']['emailAddress'])) {
                throw new LocalizedException(new Phrase('Email address missing from Apple Pay payment details!'));
            }
            $quote->setCustomerEmail($paymentData['shippingContact']['emailAddress']);
            // Save name
            if (!isset($paymentData['shippingContact']['givenName'])
                || !isset($paymentData['shippingContact']['familyName'])
            ) {
                throw new LocalizedException(new Phrase('Customer name missing from Apple Pay payment details!'));
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
            $this->createPaymentProfileForCustomer($paymentData);
        } else {
            var_dump('--- STOP: Customer NOT loggedIn ---');
            die;
            $this->createPaymentToken($paymentData);
        }
        return $quote->getId();
    }

    protected function convertToMagentoAddress($address)
    {
        if (is_string($address)) {
            $address = $this->jsonSerializer->unserialize($address);
        }

        // Retrieve the countryId from the request
        $countryId = strtoupper($address['countryCode']);
        //TODO: check address country_id here.
        if ((!$countryId || empty($countryId)) && ($countryName = $address['country'])) {
//            $countryCollection = $this->directoryRegion;
//            foreach ($countryCollection as $country) {
//                if ($countryName == $country->getName()) {
//                    $countryId = strtoupper($country->getCountryId());
//                    break;
//                }
//            }
        }

        $magentoAddress = [
            'street' => implode("\n", $address['addressLines']),
            'firstname' => $address['givenName'],
            'lastname' => $address['familyName'],
            'city' => $address['locality'],
            'country_id' => $countryId,
            'postcode' => $address['postalCode'],
            'telephone' => (isset($address['phoneNumber']) ? $address['phoneNumber'] : '0000000000')
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

    protected function createPaymentProfileForCustomer(array $applePayPayment)
    {
        $quote = $this->getQuote();
        $websiteId = $quote->getStore()->getWebsiteId();

        // Create SP customer
        $platformCustomer = $this->getPlatformCustomer($quote->getCustomerEmail(), true, $websiteId);

        //$defaultBillingAddress = $this->getCustomerSession()->getCustomer()->getDefaultBillingAddress();

        $paymentProfile = $this->createPlatformPaymentProfile(
            $platformCustomer->getId(),
            $applePayPayment['token']['paymentData'],
            $this->getCustomerSession()->getCustomer(),
            null, // $defaultBillingAddress
            $websiteId
        );

        // Set apple pay pay method on quote
        $payment = $quote->getPayment();
        $payment->setMethod(\Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider::CODE);
//        // Clear out additional information that may have been set previously in the session
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

    public function createPaymentToken(array $applePayPayment)
    {
        $platformVaultHelper = '';

        $quote = $this->getQuote();

        $paymentMethod = $platformVaultHelper->createApplePayPaymentToken(
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
        $payment->setAdditionalInformation('obscured_cc_number', $ccNumber);
        $payment->setData('cc_number', $ccNumber);
        $payment->setCcNumberEnc($payment->encrypt($ccNumber));
        $payment->setData('cc_exp_month', $paymentMethod->getMonth());
        $payment->setData('cc_exp_year', $paymentMethod->getYear());
        $payment->setData('cc_type', $platformVaultHelper->mapSubscribeProCardTypeToMagento($paymentMethod->getCardType()));
        $quote->setPayment($payment);

        // Save quote
        $payment->save();
        $quote->save();

        return $this;
    }
}
