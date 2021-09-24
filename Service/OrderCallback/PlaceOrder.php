<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service\OrderCallback;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;

class PlaceOrder
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Swarming\SubscribePro\Model\Config\ThirdPartyPayment
     */
    private $thirdPartyPaymentConfig;

    /**
     * @var \Swarming\SubscribePro\Service\OrderCallback\ResponseProcessor
     */
    private $responseProcessor;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig
     * @param \Swarming\SubscribePro\Service\OrderCallback\ResponseProcessor $responseProcessor
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Swarming\SubscribePro\Model\Config\ThirdPartyPayment $thirdPartyPaymentConfig,
        \Swarming\SubscribePro\Service\OrderCallback\ResponseProcessor $responseProcessor
    ) {
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->thirdPartyPaymentConfig = $thirdPartyPaymentConfig;
        $this->responseProcessor = $responseProcessor;
    }

    /**
     * @param array $orderRequest
     * @return array
     */
    public function execute(array $orderRequest): array
    {
        $store = $this->storeManager->getStore();
        $customer = $this->customerRepository->getById($orderRequest['platformCustomerId']);

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->assignCustomer($customer);

        $addedItemCount = 0;
        $errorMessages = [];
        foreach ($orderRequest['items'] as $productData) {
            $qty = (int)$this->getValue($productData, 'qty');
            $subscriptionData = (array)$this->getValue($productData, 'subscription', []);

            $buyRequestData = $this->prepareBuyRequestData($qty, $subscriptionData);

            $product = $this->productRepository->get($productData['productSku'], false, $store->getId());
            $quoteItem = $quote->addProduct($product, new DataObject($buyRequestData));

            if ($quoteItem instanceof \Magento\Quote\Model\Quote\Item) {
                $addedItemCount++;
            } else {
                $errorMessages[] = [
                    'subscriptionId' => $this->getValue($subscriptionData, 'id'),
                    'errorMessage' => (string)$quoteItem,
                ];
            }
        }

        $order = null;
        if ($addedItemCount > 0) {
            $this->importAddressData(
                $quote->getBillingAddress(),
                (array)$this->getValue($orderRequest, 'billingAddress', [])
            );
            $this->importAddressData(
                $quote->getShippingAddress(),
                (array)$this->getValue($orderRequest, 'shippingAddress', [])
            );

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectShippingRates();
            $shippingAddress->setShippingMethod($this->getValue($orderRequest, 'shippingMethodCode'));

            $couponCode = $orderRequest['couponCodes'][0] ?? null;
            if (!empty($couponCode)) {
                $quote->setCouponCode($couponCode);
            }

            $this->quoteRepository->save($quote);

            $this->importPaymentData(
                $quote->getPayment(),
                (array)$this->getValue($orderRequest, 'payment', []),
                (int)$store->getId()
            );
            $quote->collectTotals();
            $this->quoteRepository->save($quote);

            $order = $this->quoteManagement->submit($quote);
        }

        return $this->responseProcessor->execute(
            $this->getValue($orderRequest, 'salesOrderToken'),
            $errorMessages,
            $order
        );
    }

    /**
     * @param int $qty
     * @param array $subscriptionData
     * @return array
     */
    private function prepareBuyRequestData(int $qty, array $subscriptionData): array
    {
        $buyRequestData = [
            'qty' =>  $qty,
            'subscription_option' => [
                'item_fulfils_subscription' => true,
                'subscription_id' => $this->getValue($subscriptionData, 'id'),
                'interval' => $this->getValue($subscriptionData, 'interval'),
                'schedule_name' => $this->getValue($subscriptionData, 'scheduleName'),
                'reorder_ordinal' => $this->getValue($subscriptionData, 'reorderOrdinal'),
                'next_order_date' => $this->getValue($subscriptionData, 'nextOrderDate'),
            ]
        ];
        if (!empty($productData['custom_options'])) {
            // $buyRequestData['options'] = $productData['custom_options']; // TODO
        }
        if (!empty($productData['configurable_item_options'])) {
            // $buyRequestData['selected_configurable_option'] = $productData['configurable_item_options']; // TODO
        }
        if (!empty($productData['bundle_options'])) {
            // $buyRequestData['bundle_options'] = $productData['bundle_options']; // TODO
        }

        return $buyRequestData;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $quoteAddress
     * @param array $addressData
     * @return void
     */
    private function importAddressData(QuoteAddress $quoteAddress, array $addressData): void
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
    private function importPaymentData(QuotePayment $quotePayment, array $paymentData, int $storeId): void
    {
        $quotePayment->importData(
            [
                'method' => $this->getPaymentMethodCode($this->getValue($paymentData, 'paymentProfileType'), $storeId),
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
    private function getValue(array $request, string $field, $default = '')
    {
        return $request[$field] ?? $default;
    }
}

