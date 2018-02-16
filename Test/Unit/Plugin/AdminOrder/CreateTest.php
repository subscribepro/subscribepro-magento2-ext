<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\AdminOrder;

use Swarming\SubscribePro\Plugin\AdminOrder\Create;
use Swarming\SubscribePro\Helper\OrderItem as OrderItemHelper;
use Magento\Sales\Model\AdminOrder\Create as AdminOrderCreate;
use Magento\Sales\Model\Order\Item as OrderItem;

class CreateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\AdminOrder\Create
     */
    protected $adminOrderCreatePlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\OrderItem
     */
    protected $orderItemHelperMock;

    protected function setUp()
    {
        $this->orderItemHelperMock = $this->getMockBuilder(OrderItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->adminOrderCreatePlugin = new Create($this->orderItemHelperMock);
    }

    public function testBeforeInitFromOrderItem()
    {
        $qtyFlag = 'qty_flag';
        $subjectMock = $this->getMockBuilder(AdminOrderCreate::class)->disableOriginalConstructor()->getMock();
        $orderItemMock = $this->getMockBuilder(OrderItem::class)->disableOriginalConstructor()->getMock();

        $this->orderItemHelperMock->expects($this->once())
            ->method('cleanSubscriptionParams')
            ->with($orderItemMock, true);
        $this->orderItemHelperMock->expects($this->once())
            ->method('cleanAdditionalOptions')
            ->with($orderItemMock);

        $this->assertEquals(
            [$orderItemMock, $qtyFlag],
            $this->adminOrderCreatePlugin->beforeInitFromOrderItem($subjectMock, $orderItemMock, $qtyFlag)
        );
    }
}
