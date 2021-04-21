<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Quote;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Swarming\SubscribePro\Plugin\Quote\ToOrderItem;
use Swarming\SubscribePro\Helper\OrderItem as OrderItemHelper;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem as QuoteToOrderItem;

class ToOrderItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\Quote\ToOrderItem
     */
    protected $quoteToOrderItemPlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelperMock;

    protected function setUp(): void
    {
        $this->orderItemHelperMock = $this->getMockBuilder(OrderItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->quoteToOrderItemPlugin = new ToOrderItem($this->orderItemHelperMock);
    }

    public function testAroundCompareOptionsIfSubjectResultIsFalse()
    {
        $data = ['data'];

        $orderItemMock = $this->getMockBuilder(OrderItem::class)->disableOriginalConstructor()->getMock();
        $subjectMock = $this->getMockBuilder(QuoteToOrderItem::class)->disableOriginalConstructor()->getMock();
        $quoteItemMock = $this->getMockBuilder(AbstractItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['__wakeup'])
            ->getMockForAbstractClass();

        $proceed = function ($item, $data) use ($orderItemMock) {
            return $orderItemMock;
        };

        $this->orderItemHelperMock->expects($this->once())
            ->method('updateAdditionalOptions')
            ->with($orderItemMock);

        $this->assertSame(
            $orderItemMock,
            $this->quoteToOrderItemPlugin->aroundConvert($subjectMock, $proceed, $quoteItemMock, $data)
        );
    }
}
