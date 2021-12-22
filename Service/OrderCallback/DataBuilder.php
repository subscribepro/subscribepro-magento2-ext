<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service\OrderCallback;

use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
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
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @param \Magento\Framework\Webapi\ServiceInputProcessor $serviceInputProcessor
     * @param \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        \Magento\Framework\Webapi\ServiceInputProcessor $serviceInputProcessor,
        \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        $this->serviceInputProcessor = $serviceInputProcessor;
        $this->thirdPartyPaymentConfig = $thirdPartyPaymentConfig;
        $this->paymentTokenManagement = $paymentTokenManagement;
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
                array_merge(
                    $quoteItemData['product_option']['extension_attributes'],
                    $productOptionExtensionAttributes
                );
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
    public function importPaymentData(
        QuotePayment $quotePayment,
        array $paymentData,
        int $customerId,
        int $storeId
    ): void {
        $paymentMethodVault = GatewayConfigProvider::VAULT_CODE;
        $paymentAdditionalData = [];

        if ($this->getValue($paymentData, 'paymentProfileType') === 'external_vault') {
            $paymentToken = $this->getPaymentTokenObject(
                $this->getValue($paymentData, 'paymentToken'),
                $customerId,
                $storeId
            );

            $paymentMethodCode = $paymentToken->getPaymentMethodCode();
            $paymentMethodVault = $this->thirdPartyPaymentConfig->getVaultCodeByPaymentCode($paymentMethodCode);

            $paymentAdditionalData[PaymentTokenInterface::CUSTOMER_ID] = $customerId;
            $paymentAdditionalData[PaymentTokenInterface::PUBLIC_HASH] = $paymentToken->getPublicHash();
        }

        $quotePayment->unsMethodInstance();
        $quotePayment->setPaymentMethod($paymentMethodVault);
        $quotePayment->setMethod($paymentMethodVault);
        $quotePayment->getMethodInstance();

        $paymentAdditionalData['profile_id'] = $this->getValue($paymentData, 'paymentProfileId');
        $paymentAdditionalData['cc_type'] = $this->getValue($paymentData, 'creditcardType');
        $paymentAdditionalData['cc_number'] = $this->getValue($paymentData, 'creditcardLastDigits');
        $paymentAdditionalData['cc_exp_year'] = $this->getValue($paymentData, 'creditcardYear');
        $paymentAdditionalData['cc_exp_month'] = $this->getValue($paymentData, 'creditcardMonth');

        $quotePayment->importData(
            [
                'method' => $paymentMethodVault,
                'additional_data' => $paymentAdditionalData
            ]
        );
    }

    /**
     * @param string $paymentTokenValue
     * @param int $customerId
     * @param int $storeId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    private function getPaymentTokenObject(
        string $paymentTokenValue,
        int $customerId,
        int $storeId
    ): PaymentTokenInterface {
        $allowedPaymentCodes = $this->thirdPartyPaymentConfig->getAllowedMethods($storeId);

        $paymentToken = null;
        foreach ($allowedPaymentCodes as $paymentCode) {
            /** @var Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken */
            $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
                $paymentTokenValue,
                $paymentCode,
                $customerId
            );

            if ($paymentToken) {
                break;
            }
        }

        if (!$paymentToken instanceof PaymentTokenInterface) {
            throw new \UnexpectedValueException('Third party token is not found');
        }

        return $paymentToken;
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
