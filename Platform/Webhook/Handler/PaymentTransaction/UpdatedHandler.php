<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Platform\Webhook\Handler\PaymentTransaction;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;
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
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionCreator
     */
    private $subscriptionCreator;

    /**
     * @var \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor
     */
    private $cartItemOptionProcessor;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Transaction
     */
    private $platformTransactionService;

    /**
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator
     * @param \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor
     * @param \Swarming\SubscribePro\Platform\Service\Transaction $platformTransactionService
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Swarming\SubscribePro\Model\Quote\SubscriptionCreator $subscriptionCreator,
        \Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor $cartItemOptionProcessor,
        \Swarming\SubscribePro\Platform\Service\Transaction $platformTransactionService
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->subscriptionCreator = $subscriptionCreator;
        $this->cartItemOptionProcessor = $cartItemOptionProcessor;
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
                $this->createSubscriptions($order);
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createSubscriptions(OrderInterface $order)
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());
        if ($order->getPayment()->getMethod() == GatewayConfigProvider::CODE && $quote->getCustomerId()) {
            $this->addProductOptionsToQuoteItems($quote);
            $this->subscriptionCreator->createSubscriptions($quote, $order);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    private function addProductOptionsToQuoteItems($quote)
    {
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getProductOption()) { // Skip if options are already added
                continue;
            }
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $item = $this->cartItemOptionProcessor->addProductOptions($quoteItem->getProductType(), $quoteItem);
            $this->cartItemOptionProcessor->applyCustomOptions($item);
        }
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
