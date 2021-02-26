<?php

namespace Swarming\SubscribePro\Model\Quote;

class SubscriptionCreator
{
    const CREATED_SUBSCRIPTION_IDS = 'created_subscription_ids';
    const FAILED_SUBSCRIPTION_COUNT = 'failed_subscription_count';

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Subscription
     */
    protected $platformSubscriptionService;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManager;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $tokenManagement;

    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelper;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator
     */
    protected $quoteItemSubscriptionCreator;

    /**
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
     * @param \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator $quoteItemSubscriptionCreator
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper,
        \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator $quoteItemSubscriptionCreator
    ) {
        $this->platformCustomerManager = $platformCustomerManager;
        $this->tokenManagement = $tokenManagement;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->orderItemHelper = $orderItemHelper;
        $this->quoteItemSubscriptionCreator = $quoteItemSubscriptionCreator;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string[]
     */
    public function createSubscriptions($quote, $order)
    {
        //$paymentProfileId = $this->getPaymentProfileId($order->getPayment());
        $paymentProfileId = 6102533;
        $platformCustomer = $this->platformCustomerManager->getCustomerById($quote->getCustomerId());

        $subscriptionsSuccess = [];
        $subscriptionsFail = 0;
        /** @var \Magento\Quote\Model\Quote\Address $address */
        foreach ($quote->getAllShippingAddresses() as $address) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            foreach ($address->getAllVisibleItems() as $quoteItem) {
                if ($quoteItem->getIsVirtual() || !$this->canCreateSubscription($quoteItem)) {
                    continue;
                }

                $subscriptionId = $this->quoteItemSubscriptionCreator->create(
                    $quoteItem,
                    $platformCustomer->getId(),
                    $paymentProfileId,
                    $address
                );

                if ($subscriptionId) {
                    $this->orderItemHelper->updateOrderItem($order, $quoteItem->getItemId(), $subscriptionId);
                    $subscriptionsSuccess[] = $subscriptionId;
                } else {
                    $subscriptionsFail++;
                }
            }
        }

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if (!$quoteItem->getIsVirtual() || !$this->canCreateSubscription($quoteItem)) {
                continue;
            }

            $subscriptionId = $this->quoteItemSubscriptionCreator->create(
                $quoteItem,
                $platformCustomer->getId(),
                $paymentProfileId
            );

            if ($subscriptionId) {
                $this->orderItemHelper->updateOrderItem($order, $quoteItem->getItemId(), $subscriptionId);
                $subscriptionsSuccess[] = $subscriptionId;
            } else {
                $subscriptionsFail++;
            }
        }

        return [
            self::CREATED_SUBSCRIPTION_IDS => $subscriptionsSuccess,
            self::FAILED_SUBSCRIPTION_COUNT => $subscriptionsFail
        ];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool
     */
    protected function canCreateSubscription($quoteItem)
    {
        return $this->quoteItemHelper->getCreateNewSubscriptionAtCheckout($quoteItem)
            && !$this->quoteItemHelper->isItemFulfilsSubscription($quoteItem);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @return string
     * @throws \Exception
     */
    protected function getPaymentProfileId($payment)
    {
        $vault = $this->tokenManagement->getByPaymentId($payment->getEntityId());
        if (!$vault || !$vault->getIsActive()) {
            throw new \Exception('The vault is not found.');
        }
        return $vault->getGatewayToken();
    }
}
