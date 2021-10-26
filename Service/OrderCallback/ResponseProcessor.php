<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service\OrderCallback;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;

class ResponseProcessor
{
    private const ORDER_STATUS = 'placed';
    private const ORDER_STATE = 'open';

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    private $platformCustomerManager;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
    ) {
        $this->platformCustomerManager = $platformCustomerManager;
    }

    /**
     * @param string $salesOrderToken
     * @param array $errorMessages
     * @param \Magento\Sales\Api\Data\OrderInterface|null $order
     * @return array
     */
    public function execute(string $salesOrderToken, array $errorMessages, OrderInterface $order = null): array
    {
        return $order
            ? $this->prepareSuccessResponse($salesOrderToken, $order, $errorMessages)
            : $this->prepareFailureResponse($errorMessages);
    }

    /**
     * @param string $salesOrderToken
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order
     * @param array $errorMessages
     * @return array
     */
    private function prepareSuccessResponse(string $salesOrderToken, OrderInterface $order, array $errorMessages): array
    {
        $websiteId = (int)$order->getStore()->getWebsiteId();

        $successResponse = [
            'orderNumber' => $order->getIncrementId(),
            'orderDetails' => [
                'customerId' => $this->getPlatformCustomerId((int)$order->getCustomerId(), $websiteId),
                'customerEmail' => $order->getCustomerEmail(),
                'platformCustomerId' => $order->getCustomerId(),
                'platformOrderId' => $order->getEntityId(),
                'orderNumber' => $order->getIncrementId(),
                'salesOrderToken' => $salesOrderToken,
                'orderStatus' => self::ORDER_STATUS,
                'orderState' => self::ORDER_STATE,
                'orderDateTime' => date('c', strtotime($order->getCreatedAt())),
                'currency' => $order->getBaseCurrencyCode(),
                'discountTotal' => (string)abs($order->getBaseDiscountAmount()),
                'shippingTotal' => (string)$order->getBaseShippingAmount(),
                'taxTotal' => (string)$order->getBaseTaxAmount(),
                'total' => (string)$order->getBaseGrandTotal(),
                'billingAddress' => $this->prepareAddressData($order->getBillingAddress()),
                'items' => []
            ]
        ];

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $successResponse['orderDetails']['shippingAddress'] = $this->prepareAddressData($shippingAddress);
        }

        if (!empty($errorMessages)) {
            $successResponse['errorItems'] = $errorMessages;
        }

        foreach ($order->getAllVisibleItems() as $orderItem) {
            $successResponse['orderDetails']['items'][] = $this->prepareOrderItemData($orderItem);
        }

        return $successResponse;
    }

    /**
     * @param int $customerId
     * @param int $websiteId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getPlatformCustomerId(int $customerId, int $websiteId): string
    {
        $platformCustomer = $this->platformCustomerManager->getCustomerById($customerId, false, $websiteId);
        return (string)$platformCustomer->getId();
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $orderAddress
     * @return array
     */
    private function prepareAddressData(OrderAddressInterface $orderAddress): array
    {
        return [
            'firstName' => $orderAddress->getFirstname(),
            'lastName' => $orderAddress->getLastname(),
            'company' => $orderAddress->getCompany(),
            'street1' => $orderAddress->getStreetLine(1),
            'street2' => $orderAddress->getStreetLine(2),
            'street3' => $orderAddress->getStreetLine(3),
            'city' => $orderAddress->getCity(),
            'region' => $orderAddress->getRegionCode(),
            'postcode' => $orderAddress->getPostcode(),
            'country' => $orderAddress->getCountryId(),
            'phone' => $orderAddress->getTelephone(),
        ];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @return array
     */
    private function prepareOrderItemData(OrderItemInterface $orderItem): array
    {
        $buyRequestData = $orderItem->getProductOptionByCode('info_buyRequest');
        $subscriptionId = $buyRequestData['subscription_option']['subscription_id'] ?? null;

        return [
            'platformOrderItemId' => $orderItem->getId(),
            'productSku' => $orderItem->getSku(),
            'productName' => $orderItem->getName(),
            'shortDescription' => (string)$orderItem->getDescription(),
            'qty' => (string)$orderItem->getQtyOrdered(),
            'requiresShipping' => !$orderItem->getIsVirtual(),
            'unitPrice' => (string)$orderItem->getBasePrice(),
            'discountTotal' => (string)$orderItem->getBaseDiscountAmount(),
            'taxTotal' => (string)$orderItem->getBaseTaxAmount(),
            'lineTotal' => (string)$orderItem->getBaseRowTotal(),
            'subscriptionId' => (string)$subscriptionId
        ];
    }

    /**
     * @param array $errorMessages
     * @return array
     */
    private function prepareFailureResponse(array $errorMessages): array
    {
        return [
            'orderNumber' => null,
            'orderDetails' => null,
            'errorItems' => $errorMessages
        ];
    }
}
