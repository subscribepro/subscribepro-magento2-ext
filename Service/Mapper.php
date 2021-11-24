<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use SubscribePro\Service\DTO\AddressInterface;
use SubscribePro\Service\DTO\OrderDetailsInterface;
use SubscribePro\Service\DTO\ItemInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class Mapper
{
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
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $orderAddress
     * @return array
     */
    public function mapAddressData(OrderAddressInterface $orderAddress): array
    {
        return [
            AddressInterface::FIRST_NAME => $orderAddress->getFirstname(),
            AddressInterface::LAST_NAME => $orderAddress->getLastname(),
            AddressInterface::COMPANY => $orderAddress->getCompany(),
            AddressInterface::STREET_1 => $orderAddress->getStreetLine(1),
            AddressInterface::STREET_2 => $orderAddress->getStreetLine(2),
            AddressInterface::STREET_3 => $orderAddress->getStreetLine(3),
            AddressInterface::CITY => $orderAddress->getCity(),
            AddressInterface::REGION => $orderAddress->getRegionCode(),
            AddressInterface::POSTCODE => $orderAddress->getPostcode(),
            AddressInterface::COUNTRY => $orderAddress->getCountryId(),
            AddressInterface::PHONE => $orderAddress->getTelephone(),
        ];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function mapOrderDetailsData(
        OrderInterface $order
    ): array {
        $result = [
            // customer id in Subscribe Pro
            OrderDetailsInterface::CUSTOMER_ID => $this->getPlatformCustomerId($order),
            OrderDetailsInterface::CUSTOMER_EMAIL => $order->getCustomerEmail(),
            // customer id in Magento
            OrderDetailsInterface::PLATFORM_CUSTOMER_ID => (string)$order->getCustomerId(),
            OrderDetailsInterface::PLATFORM_ORDER_ID => $order->getEntityId(),
            OrderDetailsInterface::ORDER_NUMBER => $order->getIncrementId(),
            OrderDetailsInterface::SALES_ORDER_TOKEN => $this->getSalesOrderToken($order),
            OrderDetailsInterface::ORDER_STATUS => $order->getStatus(),
            OrderDetailsInterface::ORDER_STATE => $order->getState(),
            OrderDetailsInterface::ORDER_DATE_TIME => date('c', strtotime($order->getCreatedAt())),
            OrderDetailsInterface::CURRENCY => $order->getBaseCurrencyCode(),
            OrderDetailsInterface::DISCOUNT_TOTAL => (string)abs($order->getBaseDiscountAmount()),
            OrderDetailsInterface::SHIPPING_TOTAL => (string)$order->getBaseShippingAmount(),
            OrderDetailsInterface::TAX_TOTAL => (string)$order->getBaseTaxAmount(),
            OrderDetailsInterface::ORDER_TOTAL => (string)$order->getBaseGrandTotal(),
            OrderDetailsInterface::BILLING_ADDRESS => $this->mapAddressData($order->getBillingAddress()),
            OrderDetailsInterface::ITEMS => $this->mapOrderItemData($order)
        ];
        // Shipping address might not exist in and order
        if ($this->getShippingAddress($order)) {
            $result[OrderDetailsInterface::SHIPPING_ADDRESS] = $this->getShippingAddress($order);
        }
        return $result;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     */
    private function getSalesOrderToken(OrderInterface $order): string
    {
        $payment = $order->getPayment();
        return (string)$payment->getAdditionalInformation()[TransactionInterface::TOKEN];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array|null
     */
    private function getShippingAddress(OrderInterface $order): ?array
    {
        return $order->getShippingAddress()
            ? $this->mapAddressData($order->getShippingAddress())
            : null;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getPlatformCustomerId(OrderInterface $order): string
    {
        $websiteId = (int)$order->getStore()->getWebsiteId();
        $customerId = (int)$order->getCustomerId();
        $platformCustomer = $this->platformCustomerManager->getCustomerById($customerId, false, $websiteId);
        return (string)$platformCustomer->getId();
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    private function mapOrderItemData(OrderInterface $order): array
    {
        $result = [];
        /** @var \Magento\Sales\Api\Data\OrderItemInterface  $orderItem*/
        foreach ($order->getAllVisibleItems() as $orderItem) {
            $buyRequestData = $orderItem->getProductOptionByCode('info_buyRequest');
            $subscriptionId = $buyRequestData['subscription_option']['subscription_id'] ?? null;

            $result[] = [
                ItemInterface::PLATFORM_ORDER_ITEM_ID => $orderItem->getId(),
                ItemInterface::SKU => $orderItem->getSku(),
                ItemInterface::PRODUCT_NAME => $orderItem->getName(),
                ItemInterface::SHORT_DESCRIPTION => (string)$orderItem->getDescription(),
                ItemInterface::QTY => (string)$orderItem->getQtyOrdered(),
                ItemInterface::UNIT_PRICE => (string)$orderItem->getBasePrice(),
                ItemInterface::DISCOUNT_TOTAL => (string)$orderItem->getBaseDiscountAmount(),
                // TODO: get shipping total somewhere instead of sending 0.0
                // This depends on shipping method, magento calculates it for the whole,
                // I have not figured out if providing the correct value is worth the additional time
                ItemInterface::SHIPPING_TOTAL => '0.0',
                ItemInterface::REQUIRES_SHIPPING => !$orderItem->getIsVirtual(),
                ItemInterface::LINE_TOTAL => (string)$orderItem->getBaseRowTotal(),
                ItemInterface::SUBSCRIPTION_ID => (string)$subscriptionId
            ];
        }

        return $result;
    }
}
