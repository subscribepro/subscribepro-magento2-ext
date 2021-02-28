<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use SubscribePro\Service\Transaction\TransactionInterface;

class GetOrderStatus implements \Swarming\SubscribePro\Api\GetOrderStatusInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Swarming\SubscribePro\Api\Data\OrderPaymentStateInterfaceFactory
     */
    private $orderPaymentStateFactory;

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Swarming\SubscribePro\Api\Data\OrderPaymentStateInterfaceFactory $orderPaymentStateFactory
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Swarming\SubscribePro\Api\Data\OrderPaymentStateInterfaceFactory $orderPaymentStateFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderPaymentStateFactory = $orderPaymentStateFactory;
    }

    /**
     * @param int $orderId
     * @return \Swarming\SubscribePro\Api\Data\OrderPaymentStateInterface
     */
    public function execute($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        $isOnReview = $order->getState() === Order::STATE_PAYMENT_REVIEW;
        $transactionState = $isOnReview ? 'pending' : 'processed';
        $transactionToken = $isOnReview ? $this->getToken($order) : '';

        /** @var \Swarming\SubscribePro\Api\Data\OrderPaymentStateInterface $orderPaymentState */
        $orderPaymentState = $this->orderPaymentStateFactory->create();
        $orderPaymentState->setState($transactionState);
        $orderPaymentState->setToken($transactionToken);

        return $orderPaymentState;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    private function getToken(Order $order)
    {
        $payment = $order->getPayment();
        return (string)$payment->getAdditionalInformation(TransactionInterface::TOKEN);
    }
}
