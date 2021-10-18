<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service\OrderCallback;

use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;

class DataBuilder
{
    /**
     * @var \Magento\Framework\Webapi\ServiceInputProcessor
     */
    private $serviceInputProcessor;

    /**
     * @var \Swarming\SubscribePro\Model\Config\ThirdPartyPayment
     */
    private $thirdPartyPaymentConfig;

    /**
     * @param \Magento\Framework\Webapi\ServiceInputProcessor $serviceInputProcessor
     * @param \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
     */
    public function __construct(
        \Magento\Framework\Webapi\ServiceInputProcessor $serviceInputProcessor,
        \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
    ) {
        $this->serviceInputProcessor = $serviceInputProcessor;
        $this->thirdPartyPaymentConfig = $thirdPartyPaymentConfig;
    }

    /**
     * @param array $productData
     * @return \Magento\Quote\Api\Data\CartItemInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createQuoteItemFromProductData(array $productData): \Magento\Quote\Api\Data\CartItemInterface
    {
        $subscriptionData = (array)$this->getValue($productData, 'subscription', []);

        $quoteItemData = [
            'sku' => $productData['productSku'],
            'qty' => (int)$this->getValue($productData, 'qty'),
            'product_option' => [
                'extension_attributes' => [
                    'subscription_option' => [
                        'is_fulfilling' => 1,
                        'subscription_id' => $this->getValue($subscriptionData, 'id'),
                        'interval' => $this->getValue($subscriptionData, 'interval'),
                        'reorder_ordinal' => $this->getValue($subscriptionData, 'reorderOrdinal'),
                        'item_added_by_subscribe_pro' => true,
                        'item_fulfils_subscription' => true,
                    ]
                ]
            ]
        ];

        if ($this->getValue($productData, 'useFixedPrice', false)) {
            $quoteItemData['product_option']['extension_attributes']['subscription_option']['fixed_price']
                = $this->getValue($productData, 'fixedPrice', false);
        }

        $nextOrderDate = $this->getValue($subscriptionData, 'nextOrderDate');
        if ($nextOrderDate) {
            $quoteItemData['product_option']['extension_attributes']['subscription_option']['next_order_date']
                = $nextOrderDate;
        }

        $platformSpecificFields = (array)$this->getValue($subscriptionData, 'platformSpecificFields', []);
        $magento2SpecificFields = (array)$this->getValue($platformSpecificFields, 'magento2', []);
        $productOption = (array)$this->getValue($magento2SpecificFields, 'product_option', []);
        $productOptionExtensionAttributes = (array)$this->getValue($productOption, 'extension_attributes', []);

        if (!empty($productOptionExtensionAttributes)) {
            $quoteItemData['product_option']['extension_attributes'] =
                array_merge($quoteItemData['product_option']['extension_attributes'], $productOptionExtensionAttributes);
        }

        return $this->serviceInputProcessor->convertValue(
            $quoteItemData,
            \Magento\Quote\Api\Data\CartItemInterface::class
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $quoteAddress
     * @param array $addressData
     * @return void
     */
    public function importAddressData(QuoteAddress $quoteAddress, array $addressData): void
    {
        $quoteAddress->addData(
            [
                'firstname' => $this->getValue($addressData, 'firstName'),
                'lastname' => $this->getValue($addressData, 'lastName'),
                'street' => $this->getValue($addressData, 'street1'),
                'city' => $this->getValue($addressData, 'city'),
                'country_id' => $this->getValue($addressData, 'country'),
                'region' => $this->getValue($addressData, 'region'),
                'postcode' => $this->getValue($addressData, 'postcode'),
                'telephone' => $this->getValue($addressData, 'phone'),
            ]
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote\Payment $quotePayment
     * @param array $paymentData
     * @return void
     */
    public function importPaymentData(QuotePayment $quotePayment, array $paymentData, int $storeId): void
    {
        $paymentMethodCode = $this->getPaymentMethodCode($this->getValue($paymentData, 'paymentProfileType'), $storeId);

        $quotePayment->unsMethodInstance();
        $quotePayment->setPaymentMethod($paymentMethodCode);
        $quotePayment->setMethod($paymentMethodCode);
        $quotePayment->getMethodInstance();

        $quotePayment->importData(
            [
                'method' => $paymentMethodCode,
                'additional_data' => [
                    'profile_id' => $this->getValue($paymentData, 'paymentProfileId'),
                    'payment_method_token' => $this->getValue($paymentData, 'paymentToken'),
                    'cc_type' => $this->getValue($paymentData, 'creditcardType'),
                    'cc_number' => $this->getValue($paymentData, 'creditcardLastDigits'),
                    'cc_exp_year' => $this->getValue($paymentData, 'creditcardYear'),
                    'cc_exp_month' => $this->getValue($paymentData, 'creditcardMonth'),
                ]
            ]
        );
    }

    /**
     * @param string $paymentProfileType
     * @param int $storeId
     * @return string
     */
    private function getPaymentMethodCode(string $paymentProfileType, int $storeId): string
    {
        $paymentMethodCode = $paymentProfileType === 'external_vault'
            ? $this->thirdPartyPaymentConfig->getAllowedVault($storeId)
            : GatewayConfigProvider::VAULT_CODE;

        if (empty($paymentMethodCode)) {
            throw new \UnexpectedValueException('No third party method configured');
        }

        return $paymentMethodCode;
    }

    /**
     * @param array $request
     * @param string $field
     * @param null $default
     * @return string|array|null
     */
    public function getValue(array $request, string $field, $default = '')
    {
        return $request[$field] ?? $default;
    }
}
