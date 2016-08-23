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
        $this->productService->setWebsite($this->defaultWebsiteId);
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @dataProvider createProductDataProvider
     */
    public function testCreateProduct($websiteId, $expectedWebsiteId)
    {
        $productMock = $this->createProductMock();
        
        $this->initService($this->productPlatformService, $expectedWebsiteId);
        $this->productPlatformService->expects($this->once())
            ->method('createProduct')
            ->with(['product data'])
            ->willReturn($productMock);
        
        $this->assertSame(
            $productMock, $this->productService->createProduct(['product data'], $websiteId)
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
                'expectedWebsiteId' => $this->defaultWebsiteId,
            ]
        ];
    }

    public function testLoadProduct()
    {
        $productId = 111;
        $websiteId = 12;
        $productMock = $this->createProductMock();
        $this->initService($this->productPlatformService, $websiteId);

        $this->productPlatformService->expects($this->once())
            ->method('loadProduct')
            ->with($productId)
            ->willReturn($productMock);

        $this->assertSame(
            $productMock, $this->productService->loadProduct($productId, $websiteId)
        );
    }

    public function testSaveProduct()
    {
        $websiteId = 12;
        $productMock = $this->createProductMock();
        $this->initService($this->productPlatformService, $websiteId);

        $this->productPlatformService->expects($this->once())
            ->method('saveProduct')
            ->with($productMock)
            ->willReturn($productMock);

        $this->assertSame(
            $productMock, $this->productService->saveProduct($productMock, $websiteId)
        );
    }

    public function testLoadProducts()
    {
        $websiteId = 12;
        $sku = 'sku';
        $productsMock = [$this->createProductMock(), $this->createProductMock()];
        $this->initService($this->productPlatformService, $websiteId);

        $this->productPlatformService->expects($this->once())
            ->method('loadProducts')
            ->with($sku)
            ->willReturn($productsMock);

        $this->assertEquals(
            $productsMock, $this->productService->loadProducts($sku, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)->getMock();
    }
}
