<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote;

use Magento\Quote\Model\Quote;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionCreator;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Swarming\SubscribePro\Helper\OrderItem as OrderItemHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Swarming\SubscribePro\Platform\Manager\Customer as CustomerManager;
use Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator as QuoteItemSubscriptionCreator;

class SubscriptionCreatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionCreator
     */
    protected $subscriptionCreator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Quote\QuoteItem\SubscriptionCreator
     */
    protected $quoteItemSubscriptionCreatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $tokenManagementMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelperMock;

    protected function setUp(): void
    {
        $this->platformCustomerManagerMock = $this->getMockBuilder(CustomerManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenManagementMock = $this->getMockBuilder(PaymentTokenManagementInterface::class)->getMock();

        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItemHelperMock = $this->getMockBuilder(OrderItemHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteItemSubscriptionCreatorMock = $this->getMockBuilder(QuoteItemSubscriptionCreator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriptionCreator = new SubscriptionCreator(
            $this->platformCustomerManagerMock,
            $this->tokenManagementMock,
            $this->quoteItemHelperMock,
            $this->orderItemHelperMock,
            $this->quoteItemSubscriptionCreatorMock
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The vault is not found.
     */
    public function testExecuteIfVaultNotFound()
    {
        $paymentEntityId = 321;

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($paymentEntityId);

        $this->tokenManagementMock->expects($this->once())
            ->method('getByPaymentId')
            ->with($paymentEntityId)
            ->willReturn(null);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $this->platformCustomerManagerMock->expects($this->never())
            ->method('getCustomerById');

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->never())->method('getAllShippingAddresses');
        $quoteMock->expects($this->never())->method('getBillingAddress');
        $quoteMock->expects($this->never())->method('getAllVisibleItems');
        $quoteMock->expects($this->never())->method('getCustomerId');

        $this->subscriptionCreator->createSubscriptions($quoteMock, $orderMock);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The vault is not found.
     */
    public function testExecuteIfVaultNotActive()
    {
        $paymentEntityId = 321;

        $vaultMock = $this->createVaultMock();
        $vaultMock->expects($this->once())
            ->method('getIsActive')
            ->willReturn(false);

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($paymentEntityId);

        $this->tokenManagementMock->expects($this->once())
            ->method('getByPaymentId')
            ->with($paymentEntityId)
            ->willReturn($vaultMock);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $this->platformCustomerManagerMock->expects($this->never())
            ->method('getCustomerById');

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->never())->method('getAllShippingAddresses');
        $quoteMock->expects($this->never())->method('getBillingAddress');
        $quoteMock->expects($this->never())->method('getAllVisibleItems');
        $quoteMock->expects($this->never())->method('getCustomerId');

        $this->subscriptionCreator->createSubscriptions($quoteMock, $orderMock);
    }

    /**
     * @param bool $isVirtual
     * @param bool $isSubscriptionEnabled
     * @param bool $isItemFulfilsSubscription
     * @dataProvider createSubscriptionsIfNotSubscriptionItemDataProvider
     */
    public function testCreateSubscriptionsIfNoSubscriptionShippingItems(
        $isVirtual,
        $isSubscriptionEnabled,
        $isItemFulfilsSubscription
    ) {
        $customerId = 233;
        $paymentEntityId = 1235;
        $paymentProfileId = 1232;
        $result = [
            SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => [],
            SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => 0
        ];

        $vaultMock = $this->createVaultMock();
        $vaultMock->expects($this->once())
            ->method('getIsActive')
            ->willReturn(true);
        $vaultMock->expects($this->atLeastOnce())
            ->method('getGatewayToken')
            ->willReturn($paymentProfileId);

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($paymentEntityId);

        $this->tokenManagementMock->expects($this->once())
            ->method('getByPaymentId')
            ->with($paymentEntityId)
            ->willReturn($vaultMock);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $platformCustomerMock = $this->createPlatformCustomerMock();

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->atLeastOnce())
            ->method('getIsVirtual')
            ->willReturn($isVirtual);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->once())->method('getAllVisibleItems')->willReturn([$quoteItemMock]);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getAllShippingAddresses')->willReturn([$addressMock]);
        $quoteMock->expects($this->once())->method('getAllVisibleItems')->willReturn([$quoteItemMock]);
        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($platformCustomerMock);

        $this->quoteItemHelperMock->expects($this->any())
            ->method('isSubscriptionEnabled')
            ->with($quoteItemMock)
            ->willReturn($isSubscriptionEnabled);

        $this->quoteItemHelperMock->expects($this->any())
            ->method('isItemFulfilsSubscription')
            ->with($quoteItemMock)
            ->willReturn($isItemFulfilsSubscription);

        $this->quoteItemSubscriptionCreatorMock->expects($this->never())->method('create');

        $this->assertEquals(
            $result,
            $this->subscriptionCreator->createSubscriptions($quoteMock, $orderMock)
        );
    }

    /**
     * @return array
     */
    public function createSubscriptionsIfNotSubscriptionItemDataProvider()
    {
        return [
            'Virtual product' => [
                'isVirtual' => true,
                'isSubscriptionEnabled' => false,
                'isItemFulfilsSubscription' => false
            ],
            'Subscription not enabled' => [
                'isVirtual' => false,
                'isSubscriptionEnabled' => false,
                'isItemFulfilsSubscription' => false
            ],
            'Quote item fulfils subscription' => [
                'isVirtual' => false,
                'isSubscriptionEnabled' => true,
                'isItemFulfilsSubscription' => true
            ],
        ];
    }

    /**
     * @param int $paymentEntityId
     * @param int $paymentProfileId
     * @param int $customerId
     * @param int $platformCustomerId
     * @param \Magento\Quote\Model\Quote\Item[] $quoteItems
     * @param array $subscriptionIds
     * @param array $result
     * @dataProvider createSubscriptionsWithoutVirtualItemsDataProvider
     */
    public function testCreateSubscriptionsWithoutVirtualItems(
        $paymentEntityId,
        $paymentProfileId,
        $customerId,
        $platformCustomerId,
        $quoteItems,
        $subscriptionIds,
        $result
    ) {
        $vaultMock = $this->createVaultMock();
        $vaultMock->expects($this->once())
            ->method('getIsActive')
            ->willReturn(true);
        $vaultMock->expects($this->atLeastOnce())
            ->method('getGatewayToken')
            ->willReturn($paymentProfileId);

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($paymentEntityId);

        $this->tokenManagementMock->expects($this->once())
            ->method('getByPaymentId')
            ->with($paymentEntityId)
            ->willReturn($vaultMock);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->once())->method('getAllVisibleItems')->willReturn($quoteItems);

        $platformCustomerMock = $this->createPlatformCustomerMock();
        $platformCustomerMock->expects($this->any())->method('getId')->willReturn($platformCustomerId);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getAllShippingAddresses')->willReturn([$addressMock]);
        $quoteMock->expects($this->once())->method('getAllVisibleItems')->willReturn([]);
        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $subscriptionMap = [];
        foreach ($quoteItems as $key => $quoteItem) {
            $subscriptionMap[$key] = [
                $quoteItem,
                $platformCustomerId,
                $paymentProfileId,
                $addressMock,
                $subscriptionIds[$key]
            ];
        }

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($platformCustomerMock);

        $this->quoteItemHelperMock->expects($this->exactly(count($quoteItems)))
            ->method('isSubscriptionEnabled')
            ->willReturnMap(array_map(function ($quoteItem) {
                return [$quoteItem, true];
            }, $quoteItems));

        $this->quoteItemHelperMock->expects($this->exactly(count($quoteItems)))
            ->method('isItemFulfilsSubscription')
            ->willReturnMap(array_map(function ($quoteItem) {
                return [$quoteItem, false];
            }, $quoteItems));

        $this->quoteItemSubscriptionCreatorMock->expects($this->exactly(count($quoteItems)))
            ->method('create')
            ->willReturnMap($subscriptionMap);

        $this->assertEquals(
            $result,
            $this->subscriptionCreator->createSubscriptions($quoteMock, $orderMock)
        );
    }

    /**
     * @return array
     */
    public function createSubscriptionsWithoutVirtualItemsDataProvider()
    {
        return [
            'Empty quote' => [
                'paymentEntityId' => 123,
                'paymentProfileId' => 123123123,
                'customerId' => 22323,
                'platformCustomerId' => 4124124,
                'quoteItems' => [],
                'subscriptionIds' => [],
                'result' => [
                    SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => [],
                    SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => 0
                ]
            ],
            'All quote items valid' => [
                'paymentEntityId' => 231,
                'paymentProfileId' => 53432,
                'customerId' => 8878,
                'platformCustomerId' => 231,
                'quoteItems' => [$this->createQuoteItemMock(), $this->createQuoteItemMock()],
                'subscriptionIds' => [12, 34],
                'result' => [
                    SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => [12, 34],
                    SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => 0
                ]
            ],
            'With not created subscriptions' => [
                'paymentEntityId' => 321,
                'paymentProfileId' => 213,
                'customerId' => 1098678,
                'platformCustomerId' => 5435,
                'quoteItems' => [
                    $this->createQuoteItemMock(),
                    $this->createQuoteItemMock(),
                    $this->createQuoteItemMock()
                ],
                'subscriptionIds' => [null, 34, 56],
                'result' => [
                    SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => [34, 56],
                    SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => 1
                ]
            ],
        ];
    }

    /**
     * @param int $paymentEntityId
     * @param int $paymentProfileId
     * @param int $customerId
     * @param int $platformCustomerId
     * @param \Magento\Quote\Model\Quote\Item[] $quoteItems
     * @param array $subscriptionIds
     * @param int $virtualSubscriptionId
     * @param array $result
     * @dataProvider createSubscriptionsWithVirtualItemsDataProvider
     */
    public function testCreateSubscriptionsWithVirtualItems(
        $paymentEntityId,
        $paymentProfileId,
        $customerId,
        $platformCustomerId,
        $quoteItems,
        $subscriptionIds,
        $virtualSubscriptionId,
        $result
    ) {
        $vaultMock = $this->createVaultMock();
        $vaultMock->expects($this->once())
            ->method('getIsActive')
            ->willReturn(true);
        $vaultMock->expects($this->atLeastOnce())
            ->method('getGatewayToken')
            ->willReturn($paymentProfileId);

        $paymentMock = $this->createPaymentMock();
        $paymentMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($paymentEntityId);

        $this->tokenManagementMock->expects($this->once())
            ->method('getByPaymentId')
            ->with($paymentEntityId)
            ->willReturn($vaultMock);

        $orderMock = $this->createOrderMock();
        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->once())->method('getAllVisibleItems')->willReturn($quoteItems);

        $platformCustomerMock = $this->createPlatformCustomerMock();
        $platformCustomerMock->expects($this->any())->method('getId')->willReturn($platformCustomerId);

        $virtualQuoteItemMock = $this->createQuoteItemMock();
        $virtualQuoteItemMock->expects($this->once())->method('getIsVirtual')->willReturn(true);

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getAllShippingAddresses')->willReturn([$addressMock]);
        $quoteMock->expects($this->once())->method('getAllVisibleItems')->willReturn([$virtualQuoteItemMock]);
        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $subscriptionMap = [];
        foreach ($quoteItems as $key => $quoteItem) {
            $subscriptionMap[$key] = [
                $quoteItem,
                $platformCustomerId,
                $paymentProfileId,
                $addressMock,
                $subscriptionIds[$key]
            ];
        }
        $subscriptionMap[] = [
            $virtualQuoteItemMock,
            $platformCustomerId,
            $paymentProfileId,
            null,
            $virtualSubscriptionId
        ];

        $isSubscriptionEnabledMap = array_map(function ($quoteItem) {
            return [$quoteItem, true];
        }, $quoteItems);
        $isSubscriptionEnabledMap[] = [$virtualQuoteItemMock, true];

        $isItemFulfilsSubscriptionMap = array_map(function ($quoteItem) {
            return [$quoteItem, false];
        }, $quoteItems);
        $isItemFulfilsSubscriptionMap[] = [$virtualQuoteItemMock, false];

        $this->platformCustomerManagerMock->expects($this->once())
            ->method('getCustomerById')
            ->with($customerId)
            ->willReturn($platformCustomerMock);

        $this->quoteItemHelperMock->expects($this->exactly(count($isSubscriptionEnabledMap)))
            ->method('isSubscriptionEnabled')
            ->willReturnMap($isSubscriptionEnabledMap);

        $this->quoteItemHelperMock->expects($this->exactly(count($isItemFulfilsSubscriptionMap)))
            ->method('isItemFulfilsSubscription')
            ->willReturnMap($isItemFulfilsSubscriptionMap);

        $this->quoteItemSubscriptionCreatorMock->expects($this->exactly(count($subscriptionMap)))
            ->method('create')
            ->willReturnMap($subscriptionMap);

        $this->assertEquals(
            $result,
            $this->subscriptionCreator->createSubscriptions($quoteMock, $orderMock)
        );
    }

    /**
     * @return array
     */
    public function createSubscriptionsWithVirtualItemsDataProvider()
    {
        return [
            'Fail to create subscription from virtual product' => [
                'paymentEntityId' => 123,
                'paymentProfileId' => 65435,
                'customerId' => 543,
                'platformCustomerId' => 5346,
                'quoteItems' => [
                    $this->createQuoteItemMock(),
                    $this->createQuoteItemMock(),
                    $this->createQuoteItemMock()
                ],
                'subscriptionIds' => [123, null, 78],
                'virtualSubscriptionId' => null,
                'result' => [
                    SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => [123, 78],
                    SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => 2
                ]
            ],
            'Subscription created for virtual product' => [
                'paymentEntityId' => 231,
                'paymentProfileId' => 65435,
                'customerId' => 543,
                'platformCustomerId' => 5346,
                'quoteItems' => [
                    $this->createQuoteItemMock(),
                    $this->createQuoteItemMock(),
                    $this->createQuoteItemMock()
                ],
                'subscriptionIds' => [123, null, 78],
                'virtualSubscriptionId' => 431,
                'result' => [
                    SubscriptionCreator::CREATED_SUBSCRIPTION_IDS => [123, 78, 431],
                    SubscriptionCreator::FAILED_SUBSCRIPTION_COUNT => 1
                ]
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Api\Data\OrderInterface
     */
    private function createOrderMock()
    {
        return $this->getMockBuilder(OrderInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Sales\Api\Data\OrderPaymentInterface
     */
    private function createPaymentMock()
    {
        return $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Vault\Api\Data\PaymentTokenInterface
     */
    private function createVaultMock()
    {
        return $this->getMockBuilder(PaymentTokenInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsVirtual', '__wakeup'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomerId',
                    'getAllShippingAddresses',
                    'getBillingAddress',
                    'getAllVisibleItems',
                    '__wakeup'
                ]
            )
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Customer\CustomerInterface
     */
    private function createPlatformCustomerMock()
    {
        return $this->getMockBuilder(PlatformCustomerInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Address
     */
    private function createAddressMock()
    {
        return $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
