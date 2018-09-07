<?php

namespace Swarming\SubscribePro\Plugin\OrderItem;

use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Swarming\SubscribePro\Helper\OrderItem as OrderItemHelper;

class RepositoryPlugin
{
    /**
     * Order Item Extension Attributes Factory
     *
     * @var OrderItemExtensionFactory
     */
    protected $extensionFactory;

    protected $logger;
    protected $subscriptionOptionFactory;

    /**
     * RepositoryPlugin constructor.
     * @param OrderItemHelper $itemHelper
     */
    public function __construct(
        OrderItemExtensionFactory $extensionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Swarming\SubscribePro\Api\Data\SubscriptionOptionInterfaceFactory $subscriptionOptionFactory
    )
    {
        $this->extensionFactory = $extensionFactory;
        $this->logger = $logger;
        $this->subscriptionOptionFactory = $subscriptionOptionFactory;
    }

    /**
     * @param Magento\Sales\Api\Data\OrderItemRepositoryInterface $subject
     * @param Magento\Sales\Api\Data\OrderItemInterface $item
     */
    public function afterGet(OrderItemRepositoryInterface $subject, OrderItemInterface $item)
    {
        $subscriptionOption = $this->getSubscriptionDataFromOrderItem($item);
        if ($subscriptionOption) {
            $extensionAttributes = $item->getExtensionAttributes() ? $item->getExtensionAttributes : $this->extensionFactory->create();
            $extensionAttributes->setSubscriptionOption($subscriptionOption);
            $item->setExtensionAttributes($extensionAttributes);
        }

        return $item;
    }

    public function afterGetList(OrderItemRepositoryInterface $subject, OrderItemSearchResultInterface $searchResult)
    {
        $orderItems = $searchResult->getItems();
        foreach ($orderItems as $orderItem) {
            $this->afterGet($subject, $orderItem);
        }

        return $searchResult;
    }

    protected function getSubscriptionDataFromOrderItem(OrderItemInterface $item) {
        // Subscription data is two different places in the product options depending on frontend vs recurring order
        // 1) For the recurring order, it is in the subscription_option section of the buyRequest
        // 2) For frontend order, it is in the additional_options section

        // 1) Recurring order - buyRequest
        $buyRequest = $item->getProductOptionByCode('info_buyRequest');
        $subscriptionOptionArray = isset($buyRequest['subscription_option']) ? $buyRequest['subscription_option'] : [];
        $subscriptionOption = $this->subscriptionOptionFactory->create(['data' => $subscriptionOptionArray]);

        // 2) Frontend order - additional_options
        if ($subscriptionOption->getOption() == 'subscription' && !$subscriptionOption->getSubscriptionId()) {
            $additionalOptions = $item->getProductOptionByCode('additional_options');
            if (sizeof($additionalOptions)) {
                foreach ($additionalOptions as $additionalOption) {
                    if ($additionalOption['label'] == 'Subscription Id') {
                        $subscriptionId = $additionalOption['value'];
                        $subscriptionOption->setSubscriptionId($subscriptionId);
                    }
                }
            }
        }

        return $subscriptionOption;
    }
}
