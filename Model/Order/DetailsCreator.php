<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;

class DetailsCreator
{
    /**
     * @var Swarming\SubscribePro\Platform\Service\OrderDetails
     */
    private $orderDetailsService;

    /**
     * @var \Swarming\SubscribePro\Service\Mapper
     */
    private $mapper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\OrderDetails $orderDetailsService
     * @param \Swarming\SubscribePro\Service\Mapper $mapper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\OrderDetails $orderDetailsService,
        \Swarming\SubscribePro\Service\Mapper $mapper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->orderDetailsService = $orderDetailsService;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    /*
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return void
     */
    public function createOrderDetails(OrderInterface $order): void
    {
        try {
            /** @var \SubscribePro\Service\OrderDetails\OrderDetailsInterface $orderDetails */
            $orderDetails = $this->orderDetailsService->createOrderDetails();
            $orderDetails->setOrderDetails(
                $this->mapper->mapOrderDetailsData($order)
            );

            $this->orderDetailsService->saveOrderDetails($orderDetails);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
