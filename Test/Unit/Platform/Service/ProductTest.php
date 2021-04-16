<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Service;

use SubscribePro\Service\Product\ProductService;
use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Platform\Service\Product;

class ProductTest extends AbstractService
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\Product
     */
    protected $productService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Product\ProductService
     */
    protected $productPlatformService;

    protected function setUp()
    {
        $this->platformMock = $this->createPlatformMock();
        $this->productPlatformService = $this->getMockBuilder(ProductService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productService = new Product($this->platformMock, $this->name);
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @dataProvider createProductDataProvider
     */
    public function testCreateProduct($websiteId, $expectedWebsiteId)
    {
        $platformProductMock = $this->createPlatformProductMock();

        $this->initService($this->productPlatformService, $expectedWebsiteId);
        $this->productPlatformService->expects($this->once())
            ->method('createProduct')
            ->with(['product data'])
            ->willReturn($platformProductMock);

        $this->assertSame(
            $platformProductMock,
            $this->productService->createProduct(['product data'], $websiteId)
        );
    }

    /**
     * @return array
     */
    public function createProductDataProvider()
    {
        return [
            'With website Id' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
            ],
            'Without website Id' => [
                'websiteId' => null,
                'expectedWebsiteId' => null,
            ]
        ];
    }

    public function testLoadProduct()
    {
        $productId = 111;
        $websiteId = 12;
        $platformProductMock = $this->createPlatformProductMock();
        $this->initService($this->productPlatformService, $websiteId);

        $this->productPlatformService->expects($this->once())
            ->method('loadProduct')
            ->with($productId)
            ->willReturn($platformProductMock);

        $this->assertSame(
            $platformProductMock,
            $this->productService->loadProduct($productId, $websiteId)
        );
    }

    public function testSaveProduct()
    {
        $websiteId = 12;
        $platformProductMock = $this->createPlatformProductMock();
        $this->initService($this->productPlatformService, $websiteId);

        $this->productPlatformService->expects($this->once())
            ->method('saveProduct')
            ->with($platformProductMock)
            ->willReturn($platformProductMock);

        $this->assertSame(
            $platformProductMock,
            $this->productService->saveProduct($platformProductMock, $websiteId)
        );
    }

    public function testLoadProducts()
    {
        $websiteId = 12;
        $sku = 'sku';
        $platformProductsMock = [$this->createPlatformProductMock(), $this->createPlatformProductMock()];
        $this->initService($this->productPlatformService, $websiteId);

        $this->productPlatformService->expects($this->once())
            ->method('loadProducts')
            ->with($sku)
            ->willReturn($platformProductsMock);

        $this->assertEquals(
            $platformProductsMock,
            $this->productService->loadProducts($sku, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createPlatformProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)->getMock();
    }
}
