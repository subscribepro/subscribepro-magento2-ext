<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Checkout;

use Swarming\SubscribePro\Plugin\Checkout\Cart;
use Swarming\SubscribePro\Helper\OrderItem as OrderItemHelper;
use Magento\Checkout\Model\Cart as CheckoutCart;
use Magento\Sales\Model\Order\Item as OrderItem;

class CartTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\Checkout\Cart
     */
    protected $cartPlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelperMock;

    protected function setUp(): void
    {
        $this->orderItemHelperMock = $this->getMockBuilder(OrderItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->cartPlugin = new Cart($this->orderItemHelperMock);
    }

    public function testBeforeInitFromOrderItem()
    {
        $qtyFlag = 'some_flag';
        $subjectMock = $this->getMockBuilder(CheckoutCart::class)->disableOriginalConstructor()->getMock();
        $orderItemMock = $this->getMockBuilder(OrderItem::class)->disableOriginalConstructor()->getMock();

        $this->orderItemHelperMock->expects($this->once())
            ->method('cleanSubscriptionParams')
            ->with($orderItemMock);
        $this->orderItemHelperMock->expects($this->once())
            ->method('cleanAdditionalOptions')
            ->with($orderItemMock);

        $this->assertEquals(
            [$orderItemMock, $qtyFlag],
            $this->cartPlugin->beforeAddOrderItem($subjectMock, $orderItemMock, $qtyFlag)
        );
    }
}
