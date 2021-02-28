<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Platform\Webhook\Handler\PaymentTransaction;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;
use SubscribePro\Service\Webhook\EventInterface;
use SubscribePro\Service\Transaction\TransactionInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class UpdatedHandler implements HandlerInterface
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Transaction
     */
    private $platformTransactionService;

    /**
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Swarming\SubscribePro\Platform\Service\Transaction $platformTransactionService
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Swarming\SubscribePro\Platform\Service\Transaction $platformTransactionService
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->platformTransactionService = $platformTransactionService;
    }

    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     * @return void
     */
    public function execute(EventInterface $event)
    {
        $transaction = $this->platformTransactionService->createTransaction($event->getEventData('transaction'));

        $order = $this->getOrderByIncrementId((string)$transaction->getOrderId());
        if ($order->getStatus() !== Order::STATE_PAYMENT_REVIEW) {
            return;
        }

        switch ($transaction->getState()) {
            case TransactionInterface::STATE_SUCCEEDED:
                $this->approveOrder($order);
                break;
            case TransactionInterface::STATE_FAILED:
            case TransactionInterface::STATE_GATEWAY_PROCESSING_FAILED:
                $this->declineOrder($order);
        }
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return void
     */
    private function approveOrder(OrderInterface $order)
    {
        $orderPayment = $this->getOrderPayment($order);
        $orderPayment->accept();
        $this->orderRepository->save($order);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return void
     */
    private function declineOrder(OrderInterface $order)
    {
        $orderPayment = $this->getOrderPayment($order);
        $orderPayment->deny();
        $this->orderRepository->save($order);
    }

    /**
     * @param string $incrementId
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    private function getOrderByIncrementId($incrementId)
    {
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($incrementId);
        return $this->orderRepository->get($order->getEntityId());
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface
     */
    private function getOrderPayment(OrderInterface $order)
    {
        $orderPayment = $order->getPayment();
        if (!$orderPayment instanceof OrderPaymentInterface) {
            throw new \UnexpectedValueException(
                sprintf('Order payment is not found for %s order', $order->getIncrementId())
            );
        }
        return $orderPayment;
    }
}
