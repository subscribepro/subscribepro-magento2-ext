<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Framework\Exception\NoSuchEntityException;

class SubscriptionCreator
{
    public const CREATED_SUBSCRIPTION_IDS = 'created_subscription_ids';
    public const FAILED_SUBSCRIPTION_COUNT = 'failed_subscription_count';

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
     * @var \Swarming\SubscribePro\Model\Quote\Payment\GetPaymentProfileId
     */
    private $getPaymentProfileId;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement
     * @param \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper
     * @param \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper
     * @param \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator $quoteItemSubscriptionCreator
     * @param \Swarming\SubscribePro\Model\Quote\Payment\GetPaymentProfileId $getPaymentProfileId
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\Subscription $platformSubscriptionService,
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement,
        \Swarming\SubscribePro\Helper\QuoteItem $quoteItemHelper,
        \Swarming\SubscribePro\Helper\OrderItem $orderItemHelper,
        \Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator $quoteItemSubscriptionCreator,
        \Swarming\SubscribePro\Model\Quote\Payment\GetPaymentProfileId $getPaymentProfileId
    ) {
        $this->platformSubscriptionService = $platformSubscriptionService;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->tokenManagement = $tokenManagement;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->orderItemHelper = $orderItemHelper;
        $this->quoteItemSubscriptionCreator = $quoteItemSubscriptionCreator;
        $this->getPaymentProfileId = $getPaymentProfileId;
    }

    /**
     * @param $quote
     * @param $order
     * @return array
     * @throws NoSuchEntityException
     */
    public function createSubscriptions($quote, $order)
    {
        $platformCustomer = $this->platformCustomerManager->getCustomerById($quote->getCustomerId(), true);
        $paymentProfileId = $this->getPaymentProfileId->execute($order->getPayment(), (int)$platformCustomer->getId());

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
                    $address,
                    $quote->getBillingAddress()
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
}
