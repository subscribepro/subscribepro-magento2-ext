<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\Api\AttributeInterface;
use Swarming\SubscribePro\Helper\Product as ProductHelper;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Helper\Product
     */
    protected $productHelper;

    protected function setUp()
    {
        $this->productHelper = new ProductHelper();
    }

    public function testIsSubscriptionEnabledIfNoAttribute()
    {
        $productMock = $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
        $productMock->expects($this->once())
            ->method('getCustomAttribute')
            ->with(SubscriptionModifier::SUBSCRIPTION_ENABLED)
            ->willReturn(null);

        $this->assertFalse($this->productHelper->isSubscriptionEnabled($productMock));
    }

    /**
     * @param mixed $value
     * @param bool $result
     * @dataProvider isSubscriptionEnabledDataProvider
     */
    public function testIsSubscriptionEnabled($value, $result)
    {
        $attributeMock = $this->getMockBuilder(AttributeInterface::class)->getMock();
        $attributeMock->expects($this->once())->method('getValue')->willReturn($value);

        $productMock = $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
        $productMock->expects($this->once())
            ->method('getCustomAttribute')
            ->with(SubscriptionModifier::SUBSCRIPTION_ENABLED)
            ->willReturn($attributeMock);

        $this->assertEquals($result, $this->productHelper->isSubscriptionEnabled($productMock));
    }

    /**
     * @return array
     */
    public function isSubscriptionEnabledDataProvider()
    {
        return [
            'Value not set:false' => [
                'value' => null,
                'result' => false
            ],
            'Zero value:false' => [
                'value' => 0,
                'result' => false
            ],
            'With value:true' => [
                'value' => 1,
                'result' => true
            ],
        ];
    }
}
