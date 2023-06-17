<?php

namespace Swarming\SubscribePro\Test\Unit\Model\CatalogRule;

use Magento\Catalog\Model\Product;
use Swarming\SubscribePro\Model\CatalogRule\Inspector;
use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;
use Swarming\SubscribePro\Model\CatalogRule\InspectorRepository;

class InspectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\Inspector
     */
    protected $inspector;

    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\InspectorRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inspectorRepositoryMock;

    protected function setUp(): void
    {
        $this->inspectorRepositoryMock = $this->getMockBuilder(InspectorRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inspector = new Inspector($this->inspectorRepositoryMock);
    }

    /**
     * @param string $productType
     * @param bool $isApplied
     * @dataProvider isAppliedDataProvider
     */
    public function testIsApplied($productType, $isApplied)
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn($productType);

        $productInspector = $this->getMockBuilder(InspectorInterface::class)
            ->getMock();
        $productInspector->expects($this->once())
            ->method('isApplied')
            ->willReturn($isApplied);

        $this->inspectorRepositoryMock->expects($this->once())
            ->method('get')
            ->with($productType)
            ->willReturn($productInspector);

        $this->assertEquals($isApplied, $this->inspector->isApplied($productMock));
    }

    /**
     * @return array
     */
    public function isAppliedDataProvider()
    {
        return [
            'applied' => ['productType' => 'product_type_1', 'isApplied' => true],
            'not applied' => ['productType' => 'product_type_2', 'isApplied' => false]
        ];
    }
}
