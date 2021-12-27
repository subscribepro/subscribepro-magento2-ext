<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service\OrderCallback;

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
     * @var \Swarming\SubscribePro\Service\OrderCallback\DataBuilder
     */
    private $orderCallbackDataBuilder;

    /**
     * @var \Swarming\SubscribePro\Service\OrderCallback\ResponseProcessor
     */
    private $responseProcessor;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Swarming\SubscribePro\Service\OrderCallback\DataBuilder $orderCallbackDataBuilder
     * @param \Swarming\SubscribePro\Service\OrderCallback\ResponseProcessor $responseProcessor
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Swarming\SubscribePro\Service\OrderCallback\DataBuilder $orderCallbackDataBuilder,
        \Swarming\SubscribePro\Service\OrderCallback\ResponseProcessor $responseProcessor
    ) {
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderCallbackDataBuilder = $orderCallbackDataBuilder;
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
        $this->quoteRepository->save($quote);

        $addedItemCount = 0;
        $errorMessages = [];
        foreach ($orderRequest['items'] as $productData) {
            try {
                $this->addProductToQuote($quote, $productData);
                $addedItemCount++;
            } catch (\Exception $e) {
                $subscriptionData = (array)$this->orderCallbackDataBuilder->getValue($productData, 'subscription', []);
                $errorMessages[] = [
                    'subscriptionId' => (string)$this->orderCallbackDataBuilder->getValue($subscriptionData, 'id'),
                    'errorMessage' => $e->getMessage(),
                ];
            }

            $quote = $this->quoteRepository->get($quote->getId());
            $quote->setItems($quote->getAllVisibleItems());
        }

        $order = null;
        if ($addedItemCount > 0) {
            $this->orderCallbackDataBuilder->importAddressData(
                $quote->getBillingAddress(),
                (array)$this->orderCallbackDataBuilder->getValue($orderRequest, 'billingAddress', [])
            );
            $this->orderCallbackDataBuilder->importAddressData(
                $quote->getShippingAddress(),
                (array)$this->orderCallbackDataBuilder->getValue($orderRequest, 'shippingAddress', [])
            );

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectShippingRates();
            $shippingAddress->setShippingMethod(
                $this->orderCallbackDataBuilder->getValue($orderRequest, 'shippingMethodCode')
            );

            $couponCode = $orderRequest['couponCodes'][0] ?? null;
            if (!empty($couponCode)) {
                $quote->setCouponCode($couponCode);
            }

            $this->quoteRepository->save($quote);

            $this->orderCallbackDataBuilder->importPaymentData(
                $quote->getPayment(),
                (array)$this->orderCallbackDataBuilder->getValue($orderRequest, 'payment', []),
                (int)$customer->getId(),
                (int)$store->getId()
            );
            $quote->collectTotals();
            $this->quoteRepository->save($quote);

            $order = $this->quoteManagement->submit($quote);
        }

        return $this->responseProcessor->execute(
            $this->orderCallbackDataBuilder->getValue($orderRequest, 'salesOrderToken'),
            $errorMessages,
            $order
        );
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param array $productData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addProductToQuote(\Magento\Quote\Api\Data\CartInterface $quote, array $productData): void
    {
        $quoteItem = $this->orderCallbackDataBuilder->createQuoteItemFromProductData($productData);

        $quoteItems = $quote->getItems();
        $quoteItems[] = $quoteItem;
        $quote->setItems($quoteItems);
        $this->quoteRepository->save($quote);
    }
}
