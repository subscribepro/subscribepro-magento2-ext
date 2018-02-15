<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item as QuoteItemModel;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Helper\QuoteItem;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;

class QuoteItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item\OptionFactory
     */
    protected $itemOptionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Intl\DateTimeFactory
     */
    protected $dateTimeFactoryMock;

    protected function setUp()
    {
        $this->itemOptionFactoryMock = $this->getMockBuilder(OptionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->quoteItemHelper = new QuoteItem(
            $this->itemOptionFactoryMock,
            $this->dateTimeFactoryMock
        );
    }

    public function testGetSubscriptionParamsIfNoBuyRequest() {
        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn(null);

        $this->assertEquals([], $this->quoteItemHelper->getSubscriptionParams($quoteItemMock));
    }

    /**
     * @param string $buyRequestValue
     * @param array $subscriptionParams
     * @dataProvider getSubscriptionParamsDataProvider
     */
    public function testGetSubscriptionParams($buyRequestValue, $subscriptionParams) {
        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);

        $this->assertEquals($subscriptionParams, $this->quoteItemHelper->getSubscriptionParams($quoteItemMock));
    }

    /**
     * @return array
     */
    public function getSubscriptionParamsDataProvider()
    {
        return [
            'Without subscription params' => [
                'buyRequestValue' => json_encode([
                    'key' => 'value'
                ]),
                'subscriptionParams' => []
            ],
            'With subscription params' => [
                'buyRequestValue' => json_encode([
                    'key' => 'value',
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => ['params']
                ]),
                'subscriptionParams' => ['params']
            ]
        ];
    }

    public function testSetSubscriptionParamIfNoBuyRequest() {
        $key = 'key';
        $value = 'value';
        $buyRequestParams = [OptionProcessor::KEY_SUBSCRIPTION_OPTION => [$key => $value]];
        $productMock = $this->createProductMock();

        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->once())
            ->method('setProduct')
            ->with($productMock)
            ->willReturnSelf();
        $buyRequestMock->expects($this->once())
            ->method('setCode')
            ->with('info_buyRequest')
            ->willReturnSelf();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn(null);
        $buyRequestMock->expects($this->once())->method('setValue')->with(json_encode($buyRequestParams));

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->any())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn(null);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('addOption')->willReturn($buyRequestMock);
        $quoteItemMock->expects($this->once())->method('isObjectNew')->willReturn(true);

        $this->itemOptionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($buyRequestMock);

        $this->quoteItemHelper->setSubscriptionParam($quoteItemMock, $key, $value);
    }

    public function testSetSubscriptionParamIfQuoteItemIsNotNew() {
        $key = 'key';
        $value = 'value';
        $buyRequestParams = [OptionProcessor::KEY_SUBSCRIPTION_OPTION => [$key => $value]];
        $productMock = $this->createProductMock();
        $formattedDate = '2020-12-12';

        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->once())
            ->method('setProduct')
            ->with($productMock)
            ->willReturnSelf();
        $buyRequestMock->expects($this->once())
            ->method('setCode')
            ->with('info_buyRequest')
            ->willReturnSelf();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn(null);
        $buyRequestMock->expects($this->once())->method('setValue')->with(json_encode($buyRequestParams));

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->any())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn(null);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('addOption')->willReturn($buyRequestMock);
        $quoteItemMock->expects($this->once())->method('isObjectNew')->willReturn(false);
        $quoteItemMock->expects($this->once())->method('setUpdatedAt')->with($formattedDate);

        $this->itemOptionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($buyRequestMock);

        $dateTimeMock = $this->createDateTimeMock();
        $dateTimeMock->expects($this->once())
            ->method('format')
            ->with('Y-m-d H:i:s')
            ->willReturn($formattedDate);

        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($dateTimeMock);

        $this->quoteItemHelper->setSubscriptionParam($quoteItemMock, $key, $value);
    }

    public function testSetSubscriptionParam() {
        $key = 'newkey';
        $value = 'newvalue';
        $buyRequestParams = [
            'some' => 'param',
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => ['key' => 'value']
        ];
        $updatedBuyRequestParams = [
            'some' => 'param',
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                'key' => 'value',
                $key => $value
            ]
        ];

        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->any())->method('getValue')->willReturn(json_encode($buyRequestParams));
        $buyRequestMock->expects($this->once())->method('setValue')->with(json_encode($updatedBuyRequestParams));

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->any())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);
        $quoteItemMock->expects($this->once())->method('addOption')->willReturn($buyRequestMock);
        $quoteItemMock->expects($this->once())->method('isObjectNew')->willReturn(true);

        $this->itemOptionFactoryMock->expects($this->never())->method('create');

        $this->quoteItemHelper->setSubscriptionParam($quoteItemMock, $key, $value);
    }

    /**
     * @param string $buyRequestValue
     * @param null|string $interval
     * @dataProvider getSubscriptionIntervalDataProvider
     */
    public function testGetSubscriptionInterval($buyRequestValue, $interval) {
        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);

        $this->assertEquals($interval, $this->quoteItemHelper->getSubscriptionInterval($quoteItemMock));
    }

    /**
     * @return array
     */
    public function getSubscriptionIntervalDataProvider()
    {
        return [
            'Without subscription params' => [
                'buyRequestValue' => json_encode([]),
                'interval' => null
            ],
            'Without interval' => [
                'buyRequestValue' => json_encode([
                    'key' => 'value',
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => ['params']
                ]),
                'interval' => null
            ],
            'With interval' => [
                'buyRequestValue' => json_encode([
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::INTERVAL => 'monthly'
                    ]
                ]),
                'interval' => 'monthly'
            ]
        ];
    }

    /**
     * @param string $buyRequestValue
     * @param null|string $subscriptionOption
     * @dataProvider getSubscriptionOptionDataProvider
     */
    public function testGetSubscriptionOption($buyRequestValue, $subscriptionOption) {
        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);

        $this->assertEquals(
            $subscriptionOption,
            $this->quoteItemHelper->getSubscriptionOption($quoteItemMock)
        );
    }

    /**
     * @return array
     */
    public function getSubscriptionOptionDataProvider()
    {
        return [
            'Without subscription params' => [
                'buyRequestValue' => json_encode([
                    'key' => 'value'
                ]),
                'subscriptionOption' => null
            ],
            'Without subscription option' => [
                'buyRequestValue' => json_encode([
                    'some_key' => [],
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => ['params']
                ]),
                'subscriptionOption' => null
            ],
            'With subscription option' => [
                'buyRequestValue' => json_encode([
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::OPTION => 'subscribe'
                    ]
                ]),
                'subscriptionOption' => 'subscribe'
            ]
        ];
    }

    /**
     * @param string $buyRequestValue
     * @param bool $isFulfilsSubscription
     * @dataProvider isFulfilsSubscriptionDataProvider
     */
    public function testIsFulfilsSubscription($buyRequestValue, $isFulfilsSubscription) {
        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);

        $this->assertEquals(
            $isFulfilsSubscription,
            $this->quoteItemHelper->isFulfilsSubscription($quoteItemMock)
        );
    }

    /**
     * @return array
     */
    public function isFulfilsSubscriptionDataProvider()
    {
        return [
            'Without subscription params' => [
                'buyRequestValue' => json_encode([
                    'key' => 'value'
                ]),
                'isFulfilsSubscription' => false
            ],
            'Without fulfils subscription' => [
                'buyRequestValue' => json_encode([
                    'some_key' => [],
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => ['params']
                ]),
                'isFulfilsSubscription' => false
            ],
            'With fulfils subscription: false' => [
                'buyRequestValue' => json_encode([
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::IS_FULFILLING => 0
                    ]
                ]),
                'isFulfilsSubscription' => false
            ],
            'With fulfils subscription: true' => [
                'buyRequestValue' => json_encode([
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                        SubscriptionOptionInterface::IS_FULFILLING => 1
                    ]
                ]),
                'isFulfilsSubscription' => true
            ]
        ];
    }

    /**
     * @param string $subscriptionOption
     * @param bool $isSubscriptionEnabled
     * @dataProvider isSubscriptionEnabledDataProvider
     */
    public function testIsSubscriptionEnabled($subscriptionOption, $isSubscriptionEnabled) {
        $buyRequestValue = json_encode([
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                SubscriptionOptionInterface::OPTION => $subscriptionOption
            ]
        ]);

        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);

        $this->assertEquals(
            $isSubscriptionEnabled,
            $this->quoteItemHelper->isSubscriptionEnabled($quoteItemMock)
        );
    }

    /**
     * @return array
     */
    public function isSubscriptionEnabledDataProvider()
    {
        return [
            'Not subscription option' => [
                'subscriptionOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'isSubscriptionEnabled' => false
            ],
            'Subscription option' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'isSubscriptionEnabled' => true
            ]
        ];
    }

    /**
     * @param string $subscriptionOption
     * @param bool $isFulfilsSubscription
     * @param bool $hasSubscription
     * @dataProvider hasSubscriptionDataProvider
     */
    public function testHasSubscription($subscriptionOption, $isFulfilsSubscription, $hasSubscription) {
        $buyRequestValue = json_encode([
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => [
                SubscriptionOptionInterface::OPTION => $subscriptionOption,
                SubscriptionOptionInterface::IS_FULFILLING => $isFulfilsSubscription,
            ]
        ]);

        $buyRequestMock = $this->createOptionMock();
        $buyRequestMock->expects($this->any())->method('getValue')->willReturn($buyRequestValue);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->any())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);

        $this->assertEquals(
            $hasSubscription,
            $this->quoteItemHelper->hasSubscription($quoteItemMock)
        );
    }

    /**
     * @return array
     */
    public function hasSubscriptionDataProvider()
    {
        return [
            'No subscription option: no fulfils subscription: no subscription' => [
                'subscriptionOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'isFulfilsSubscription' => false,
                'hasSubscription' => false
            ],
            'Subscription option: no fulfils subscription: has subscription' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'isFulfilsSubscription' => false,
                'hasSubscription' => true
            ],
            'No subscription option: fulfils subscription: has subscription' => [
                'subscriptionOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'isFulfilsSubscription' => true,
                'hasSubscription' => true
            ],
            'Subscription option: fulfils subscription: has subscription' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'isFulfilsSubscription' => true,
                'hasSubscription' => true
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItemModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptionByCode', 'addOption', 'getProduct', 'isObjectNew', 'setUpdatedAt', '__sleep', '__wakeup'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface
     */
    private function createOptionMock()
    {
        return $this->getMockBuilder(OptionInterface::class)
            ->setMethods(['setProduct', 'getValue', 'setCode', 'setValue'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\DateTime
     */
    private function createDateTimeMock()
    {
        return $this->getMockBuilder(\DateTime::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }
}
