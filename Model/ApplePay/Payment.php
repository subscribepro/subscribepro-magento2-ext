<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Swarming\SubscribePro\Model\ApplePay\Core as ApplePayCore;

class Payment extends ApplePayCore
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
            $this->createPaymentToken($paymentData);
        }
        return $this;
    }

    public function placeOrder()
    {
        var_dump(__METHOD__);
        die;
        return $this;
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

        $magentoAddress = array(
            'street' => implode("\n", $address['addressLines']),
            'firstname' => $address['givenName'],
            'lastname' => $address['familyName'],
            'city' => $address['locality'],
            'country_id' => $countryId,
            'postcode' => $address['postalCode'],
            'telephone' => (isset($address['phoneNumber']) ? $address['phoneNumber'] : '0000000000')
        );

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
        /** @var $platformVaultHelper */
//        $platformVaultHelper = Mage::helper('autoship/platform_vault');
        /** @var $platformCustomerHelper */
//        $platformCustomerHelper = Mage::helper('autoship/platform_customer');

        $quote = $this->getQuote();
        var_dump(__METHOD__);
        var_dump($quote->getId());
        die;

        // Create SP customer
//        $platformCustomer = $platformCustomerHelper->createOrUpdatePlatformCustomer($quote->getCustomer());
        // Create payment profile
        /*$paymentProfile = $platformVaultHelper->createApplePayPaymentProfile(
            $platformCustomer->getId(),
            $quote->getCustomer(),
            $quote->getBillingAddress(),
            $applePayPayment['token']['paymentData']
        );*/

        return '';

        // Set apple pay pay method on quote
//        $payment = $quote->getPayment();
//        $payment->setMethod(SubscribePro_Autoship_Model_Payment_Method_Applepay::METHOD_CODE);
//        // Clear out additional information that may have been set previously in the session
//        $payment->setAdditionalInformation(array());
//        $payment->setAdditionalInformation('save_card', false);
//        $payment->setAdditionalInformation('is_new_card', false);
//        $payment->setAdditionalInformation('payment_token', $paymentProfile->getPaymentToken());
//        $payment->setAdditionalInformation('payment_profile_id', $paymentProfile->getId());
//        $payment->setAdditionalInformation('is_third_party', false);
//        $payment->setAdditionalInformation('subscribe_pro_order_token', '');
//        // CC Number
//        $ccNumber = $paymentProfile->getCreditcardFirstDigits() . 'XXXXXX' . $paymentProfile->getCreditcardLastDigits();
//        $payment->setAdditionalInformation('obscured_cc_number', $ccNumber);
//        $payment->setData('cc_number', $ccNumber);
//        $payment->setCcNumberEnc($payment->encrypt($ccNumber));
//        $payment->setData('cc_exp_month', $paymentProfile->getCreditcardMonth());
//        $payment->setData('cc_exp_year', $paymentProfile->getCreditcardYear());
//        $payment->setData('cc_type', $platformVaultHelper->mapSubscribeProCardTypeToMagento($paymentProfile->getCreditcardType()));
//        $quote->setPayment($payment);
//
//        // Recalculate quote
//        $payment->save();
//        $quote->save();
//
//        return $this;
    }

    public function createPaymentToken(array $applePayPayment)
    {
        //$platformVaultHelper = Mage::helper('autoship/platform_vault');
        $platformVaultHelper = '';

        $quote = $this->getQuote();

        $paymentMethod = $platformVaultHelper->createApplePayPaymentToken(
            $quote->getBillingAddress(),
            $applePayPayment['token']['paymentData']
        );

        // Set apple pay pay method on quote
        $payment = $quote->getPayment();
        // TODO: need a constant
        $payment->setMethod('subscribe_pro_applepay');
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
