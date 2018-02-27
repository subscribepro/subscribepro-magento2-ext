<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Magento\Quote\Model\Quote\Item as OrderItemModel;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Helper\OrderItem as OrderItemHelper;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;

class OrderItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepositoryMock;

    protected function setUp()
    {
        $this->orderItemRepositoryMock = $this->getMockBuilder(OrderItemRepositoryInterface::class)->getMock();

        $this->orderItemHelper = new OrderItemHelper($this->orderItemRepositoryMock);
    }

    /**
     * @param array $buyRequestParams
     * @dataProvider updateAdditionalOptionsIfEmptySubscriptionOptionDataProvider
     */
    public function testUpdateAdditionalOptionsIfEmptySubscriptionOption($buyRequestParams)
    {
        $orderItemMock = $this->createOrderItemMock();
        $orderItemMock->expects($this->once())
            ->method('getProductOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestParams);
        $orderItemMock->expects($this->never())->method('setProductOptions');

        $this->orderItemHelper->updateAdditionalOptions($orderItemMock);
    }

    /**
     * @return array
     */
    public function updateAdditionalOptionsIfEmptySubscriptionOptionDataProvider()
    {
        return [
            'Buy request is null' => [
                'buyRequestParams' => null,
            ],
            'No subscription option in buy request' => [
                'buyRequestParams' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        'key' => 'value'
                    ]
                ],
            ],
            'Subscription option is empty' => [
                'buyRequestParams' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        'key' => 'value',
                        SubscriptionOptionInterface::OPTION => ''
                    ]
                ],
            ],
        ];
    }

    public function testUpdateAdditionalOptionsIfNotOneTimePurchaseOption()
    {
        $subscriptionOption = 'some_unknown_option';
        $buyRequestParams = [
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                SubscriptionOptionInterface::OPTION => $subscriptionOption
            ]
        ];
        $additionalOptions = [['options']];
        $productOptions = ['key' => 'option', 'additional_options' => $additionalOptions];

        $orderItemMock = $this->createOrderItemMock();
        $orderItemMock->expects($this->exactly(2))
            ->method('getProductOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestParams);
        $orderItemMock->expects($this->exactly(2))->method('getProductOptions')->willReturn($productOptions);
        $orderItemMock->expects($this->once())->method('setProductOptions')->with($productOptions);

        $this->orderItemHelper->updateAdditionalOptions($orderItemMock);
    }

    public function testUpdateAdditionalOptionsIfOneTimePurchaseOption()
    {
        $subscriptionOption = PlatformProductInterface::SO_ONETIME_PURCHASE;
        $buyRequestParams = [
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                'somekey' => ['value'],
                SubscriptionOptionInterface::OPTION => $subscriptionOption
            ]
        ];
        $productOptions = ['product' => 'option', 'additional_options' => [['options']]];
        $expectedProductOptions = [
            'product' => 'option',
            'additional_options' => [
                ['options'],
                [
                    'label' => (string)__('Delivery'),
                    'value' => (string)__('One Time')
                ]
            ]
        ];

        $orderItemMock = $this->createOrderItemMock();
        $orderItemMock->expects($this->once())
            ->method('getProductOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestParams);
        $orderItemMock->expects($this->exactly(2))->method('getProductOptions')->willReturn($productOptions);
        $orderItemMock->expects($this->once())->method('setProductOptions')->with($expectedProductOptions);

        $this->orderItemHelper->updateAdditionalOptions($orderItemMock);
    }

    /**
     * @param int|null $subscriptionId
     * @param array $buyRequestParams
     * @param array $productOptions
     * @param array $expectedProductOptions
     * @dataProvider updateAdditionalOptionsWithSubscriptionIdDataProvider
     */
    public function testUpdateAdditionalOptionsWithSubscriptionId(
        $subscriptionId,
        $buyRequestParams,
        $productOptions,
        $expectedProductOptions
    ) {
        $orderItemMock = $this->createOrderItemMock();
        $orderItemMock->expects($this->any())
            ->method('getProductOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestParams);
        $orderItemMock->expects($this->exactly(2))->method('getProductOptions')->willReturn($productOptions);
        $orderItemMock->expects($this->once())->method('setProductOptions')->with($expectedProductOptions);

        $this->orderItemHelper->updateAdditionalOptions($orderItemMock, $subscriptionId);
    }

    /**
     * @return array
     */
    public function updateAdditionalOptionsWithSubscriptionIdDataProvider()
    {
        return [
            'With subscription ID in buy request' => [
                'subscriptionId' => null,
                'buyRequestParams' => [
                    'key' => 'value',
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => PlatformProductInterface::SO_SUBSCRIPTION,
                        SubscriptionOptionInterface::INTERVAL => 'interval_value',
                        SubscriptionOptionInterface::SUBSCRIPTION_ID => 3444
                    ]
                ],
                'productOptions' => [
                    'product' => 'option',
                    'additional_options' => [['label' => 'option']]
                ],
                'expectedProductOptions' => [
                    'product' => 'option',
                    'additional_options' => [
                        ['label' => 'option'],
                        [
                            'label' => (string)__('Regular Delivery'),
                            'value' => (string)__('interval_value')
                        ],
                        [
                            'label' => (string)__('Subscription Id'),
                            'value' => 3444,
                        ]
                    ]
                ],
            ],
            'With subscription ID' => [
                'subscriptionId' => 5554,
                'buyRequestParams' => [
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'some_option_value',
                        SubscriptionOptionInterface::INTERVAL => 'another_value',
                    ]
                ],
                'productOptions' => [
                    'product' => 'option',
                    'additional_options' => [['key' => ['value']]]
                ],
                'expectedProductOptions' => [
                    'product' => 'option',
                    'additional_options' => [
                        ['key' => ['value']],
                        [
                            'label' => (string)__('Regular Delivery'),
                            'value' => (string)__('another_value')
                        ],
                        [
                            'label' => (string)__('Subscription Id'),
                            'value' => 5554,
                        ]
                    ]
                ],
            ],
        ];
    }

    public function testUpdateOrderItem()
    {
        $quoteItemId = 32315;
        $subscriptionId = null;
        $subscriptionOption = PlatformProductInterface::SO_ONETIME_PURCHASE;
        $buyRequestParams = [
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                'somekey' => ['value'],
                SubscriptionOptionInterface::OPTION => $subscriptionOption
            ]
        ];
        $productOptions = ['product' => 'option', 'additional_options' => [['options']]];
        $expectedProductOptions = [
            'product' => 'option',
            'additional_options' => [
                ['options'],
                [
                    'label' => (string)__('Delivery'),
                    'value' => (string)__('One Time')
                ]
            ]
        ];

        $orderItemMock = $this->createOrderItemMock();
        $orderItemMock->expects($this->once())
            ->method('getProductOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestParams);
        $orderItemMock->expects($this->exactly(2))->method('getProductOptions')->willReturn($productOptions);
        $orderItemMock->expects($this->once())->method('setProductOptions')->with($expectedProductOptions);

        $order = $this->createOrderMock();
        $order->expects($this->once())
            ->method('getItemByQuoteItemId')
            ->with($quoteItemId)
            ->willReturn($orderItemMock);

        $this->orderItemRepositoryMock->expects($this->once())
            ->method('save')
            ->with($orderItemMock);

        $this->orderItemHelper->updateOrderItem($order, $quoteItemId, $subscriptionId);
    }

    public function testCleanSubscriptionParamsIfEmptySubscriptionParams()
    {
        $subscriptionParams = [];
        $buyRequestParams = [
            'option' => ['params'],
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => $subscriptionParams
        ];

        $orderItemMock = $this->createOrderItemMock();
        $orderItemMock->expects($this->once())
            ->method('getProductOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestParams);

        $orderItemMock->expects($this->never())->method('getProductOptions');
        $orderItemMock->expects($this->never())->method('setProductOptions');

        $this->orderItemHelper->cleanSubscriptionParams($orderItemMock, false);
    }

    /**
     * @param bool $deleteAll
     * @param array $buyRequestParams
     * @param array $productOptions
     * @param array $expectedProductOptions
     * @dataProvider cleanSubscriptionParamsDataProvider
     */
    public function testCleanSubscriptionParams(
        $deleteAll,
        $buyRequestParams,
        $productOptions,
        $expectedProductOptions
    ) {
        $orderItemMock = $this->createOrderItemMock();
        $orderItemMock->expects($this->once())
            ->method('getProductOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestParams);

        $orderItemMock->expects($this->once())->method('getProductOptions')->willReturn($productOptions);
        $orderItemMock->expects($this->once())->method('setProductOptions')->with($expectedProductOptions);

        $this->orderItemHelper->cleanSubscriptionParams($orderItemMock, $deleteAll);
    }

    /**
     * @return array
     */
    public function cleanSubscriptionParamsDataProvider()
    {
        return [
            'With deleteAll' => [
                'deleteAll' => true,
                'buyRequestParams' => [
                    'key' => 'value',
                ],
                'productOptions' => [
                    'info_buyRequest' => [
                        OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                            'key' => 'value'
                        ]
                    ],
                    'additional_options' => [['label' => 'option']]
                ],
                'expectedProductOptions' => [
                    'info_buyRequest' => [],
                    'additional_options' => [['label' => 'option']]
                ],
            ],
            'Without deleteAll:not empty subscription params' => [
                'deleteAll' => false,
                'buyRequestParams' => [
                    'option' => ['params'],
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => PlatformProductInterface::SO_SUBSCRIPTION,
                        SubscriptionOptionInterface::IS_FULFILLING => true,
                        SubscriptionOptionInterface::SUBSCRIPTION_ID => 4531
                    ]
                ],
                'productOptions' => [
                    'info_buyRequest' => [
                        OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                            SubscriptionOptionInterface::OPTION => PlatformProductInterface::SO_SUBSCRIPTION,
                            SubscriptionOptionInterface::IS_FULFILLING => true,
                            SubscriptionOptionInterface::SUBSCRIPTION_ID => 4531
                        ]
                    ],
                ],
                'expectedProductOptions' => [
                    'info_buyRequest' => [
                        OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                            SubscriptionOptionInterface::OPTION => PlatformProductInterface::SO_SUBSCRIPTION,
                        ]
                    ],
                ],
            ],
        ];
    }

    public function testCleanAdditionalOptions()
    {
        $productOptions = [
            'key' => 'val',
            'additional_options' => [
                [
                    'label' => 'Some label',
                    'value' => 'Some value'
                ],
                [
                    'label' => (string)__('Delivery'),
                    'value' => 'One Time'
                ],
                [
                    'label' => (string)__('Regular Delivery'),
                    'value' => 'Weekly'
                ],
                [
                    'label' => (string)__('Subscription Id'),
                    'value' => 123
                ],
                [
                    'label' => 'Main text',
                    'value' => 'Main value'
                ],
            ]
        ];
        $expectedProductOptions = [
            'key' => 'val',
            'additional_options' => [
                [
                    'label' => 'Main text',
                    'value' => 'Main value'
                ],
                [
                    'label' => 'Some label',
                    'value' => 'Some value'
                ],
            ]
        ];

        $orderItemMock = $this->createOrderItemMock();
        $orderItemMock->expects($this->exactly(2))->method('getProductOptions')->willReturn($productOptions);
        $orderItemMock->expects($this->once())->method('setProductOptions')->with($expectedProductOptions);

        $this->orderItemHelper->cleanAdditionalOptions($orderItemMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Model\Order\Item
     */
    private function createOrderItemMock()
    {
        return $this->getMockBuilder(OrderItem::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Model\Order
     */
    private function createOrderMock()
    {
        return $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
    }
}
