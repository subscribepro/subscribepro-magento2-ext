<?php

namespace Swarming\SubscribePro\Ui\Component\Listing\Column;

class OrderContainsSubscription extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Swarming\SubscribePro\Helper\Order
     */
    protected $orderHelper;

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Swarming\SubscribePro\Helper\Order $orderHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Swarming\SubscribePro\Helper\Order $orderHelper,
        array $components = [],
        array $data = [])
    {
        $this->orderRepository = $orderRepository;
        $this->orderHelper = $orderHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $order = $this->orderRepository->get($item['entity_id']);
                $value = 'No';

                if ($this->orderHelper->isNewSubscriptionOrder($order)) {
                    $value = 'New';
                } else if ($this->orderHelper->isRecurringOrder($order)) {
                    $value = 'Recurring';
                }
                $item[$this->getData('name')] = $value;
            }
        }
        return $dataSource;
    }
}
