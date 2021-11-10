<?php

namespace Swarming\SubscribePro\Test\Unit\Model\CatalogRule;

use Swarming\SubscribePro\Model\CatalogRule\InspectorInterface;
use Swarming\SubscribePro\Model\CatalogRule\InspectorRepository;
use Magento\Framework\ObjectManagerInterface;
use stdClass;

class InspectorRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\InspectorRepository
     */
    protected $inspectorRepository;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var string
     */
    protected $defaultInspector = '\Default\Inspector\Class';

    /**
     * @var array
     */
    protected $inspectors = [
        'product_type_1' => '\Type\Inspector\One',
        'product_type_2' => '\Type\Inspector\Two'
    ];

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $this->inspectorRepository = new InspectorRepository(
            $this->objectManagerMock,
            $this->defaultInspector,
            $this->inspectors
        );
    }

    public function testGetDefaultInspector()
    {
        $inspectorMock = $this->getMockBuilder(InspectorInterface::class)
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with($this->defaultInspector)
            ->willReturn($inspectorMock);

        $this->assertSame($inspectorMock, $this->inspectorRepository->get('product_type'));
    }

    /**
     * @param string $productType
     * @dataProvider getInspectorDataProvider
     */
    public function testGetInspector($productType)
    {
        $inspectorMock = $this->getMockBuilder(InspectorInterface::class)
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with($this->inspectors[$productType])
            ->willReturn($inspectorMock);

        $this->assertSame($inspectorMock, $this->inspectorRepository->get($productType));
    }

    /**
     * @return array
     */
    public function getInspectorDataProvider()
    {
        return [
            'product type 1' => [
                'productType' => 'product_type_1'
            ],
            'product type 2' => [
                'productType' => 'product_type_2'
            ]
        ];
    }

    public function testGetInspectorIfWrongClass()
    {
        $inspectorMock = $this->getMockBuilder(stdClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with($this->defaultInspector)
            ->willReturn($inspectorMock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Catalog rule inspector must implement Swarming\SubscribePro\Model\CatalogRule\InspectorInterface interface'
        );
        $this->assertSame($inspectorMock, $this->inspectorRepository->get('product_type'));
    }
}
