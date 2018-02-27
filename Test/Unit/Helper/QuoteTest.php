<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Swarming\SubscribePro\Helper\Quote;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;

class QuoteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    protected function setUp()
    {
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->quoteHelper = new Quote(
            $this->quoteItemHelperMock
        );
    }

    /**
     * @param null|array $items
     * @dataProvider hasSubscriptionIfNoItemsDataProvider
     */
    public function testHasSubscriptionIfNoItems($items)
    {
        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getItems')->willReturn($items);

        $this->assertFalse($this->quoteHelper->hasSubscription($quoteMock));
    }

    /**
     * @return array
     */
    public function hasSubscriptionIfNoItemsDataProvider()
    {
        return [
            'Null items' => ['items' => null],
            'Empty items' => ['items' => []],
        ];
    }

    /**
     * @param null|\Magento\Quote\Model\Quote\Item[] $quoteItems
     * @param bool[] $hasSubscriptionItemResults
     * @param bool $hasSubscription
     * @dataProvider hasSubscriptionDataProvider
     */
    public function testHasSubscription(
        $quoteItems,
        $hasSubscriptionItemResults,
        $hasSubscription
    ) {
        $quoteItemHasSubscriptionMap = [];
        foreach ($quoteItems as $key => $quoteItem) {
            $quoteItemHasSubscriptionMap[] = [$quoteItems[$key], $hasSubscriptionItemResults[$key]];
        }

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getItems')->willReturn($quoteItems);

        $this->quoteItemHelperMock->expects($this->exactly(count($quoteItems)))
            ->method('hasSubscription')
            ->willReturnMap($quoteItemHasSubscriptionMap);

        $this->assertEquals(
            $hasSubscription,
            $this->quoteHelper->hasSubscription($quoteMock)
        );
    }

    /**
     * @return array
     */
    public function hasSubscriptionDataProvider()
    {
        return [
            'Empty items' => [
                'quoteItems' => [],
                'hasSubscriptionItemResults' => null,
                'hasSubscription' => false
            ],
            'Items don\'t have subscription' => [
                'quoteItems' => [$this->createQuoteItemMock(), $this->createQuoteItemMock()],
                'hasSubscriptionItemResults' => [false, false],
                'hasSubscription' => false
            ],
            'One of items has subscription' => [
                'quoteItems' => [
                    $this->createQuoteItemMock(),
                    $this->createQuoteItemMock(),
                    $this->createQuoteItemMock()
                ],
                'hasSubscriptionItemResults' => [false, false, true],
                'hasSubscription' => true
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['__clone', '__wakeUp'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartInterface
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(CartInterface::class)->getMock();
    }
}
