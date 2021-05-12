<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Manager;

use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;
use Swarming\SubscribePro\Platform\Manager\Product;
use Swarming\SubscribePro\Platform\Service\Product as ProductService;
use Swarming\SubscribePro\Platform\Storage\Product as ProductStorage;
use Magento\Catalog\Api\Data\ProductInterface;

class ProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $productManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Storage\Product
     */
    protected $platformProductStorageMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Product
     */
    protected $platformProductServiceMock;

    protected function setUp(): void
    {
        $this->platformProductServiceMock = $this->getMockBuilder(ProductService::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformProductStorageMock = $this->getMockBuilder(ProductStorage::class)
            ->disableOriginalConstructor()->getMock();

        $this->productManager = new Product(
            $this->platformProductServiceMock,
            $this->platformProductStorageMock
        );
    }

    public function testGetProductIfCached()
    {
        $sku = 'sku';
        $websiteId = 23;
        $platformProductMock = $this->createPlatformProductMock();

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn($platformProductMock);

        $this->platformProductServiceMock->expects($this->never())->method('loadProducts');
        $this->platformProductStorageMock->expects($this->never())->method('save');

        $this->assertSame($platformProductMock, $this->productManager->getProduct($sku, $websiteId));
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage Product is not found on Subscribe Pro platform.
     */
    public function testFailToGetProductIfNotFound()
    {
        $sku = 'sku';
        $websiteId = 23;

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn(null);

        $this->platformProductServiceMock->expects($this->once())
            ->method('loadProducts')
            ->with($sku, $websiteId)
            ->willReturn([]);

        $this->platformProductStorageMock->expects($this->never())->method('save');

        $this->productManager->getProduct($sku, $websiteId);
    }

    public function testGetProduct()
    {
        $sku = 'sku';
        $websiteId = 23;
        $platformProductMock = $this->createPlatformProductMock();

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn(null);

        $this->platformProductServiceMock->expects($this->once())
            ->method('loadProducts')
            ->with($sku, $websiteId)
            ->willReturn([$platformProductMock]);

        $this->platformProductStorageMock->expects($this->once())
            ->method('save')
            ->with($platformProductMock, $websiteId);

        $this->assertSame($platformProductMock, $this->productManager->getProduct($sku, $websiteId));
    }

    public function testSaveNewProduct()
    {
        $sku = 'sku';
        $websiteId = 23;

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())->method('setSku')->with($sku)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setPrice')->with(100)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setName')->with('product name')->willReturnSelf();

        $productMock = $this->getMockBuilder(ProductInterface::class)->getMock();
        $productMock->expects($this->any())->method('getSku')->willReturn($sku);
        $productMock->expects($this->any())->method('getPrice')->willReturn(100);
        $productMock->expects($this->any())->method('getName')->willReturn('product name');

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn(null);
        $this->platformProductStorageMock->expects($this->never())->method('remove');

        $this->platformProductServiceMock->expects($this->once())
            ->method('loadProducts')
            ->with($sku, $websiteId)
            ->willReturn([]);

        $this->platformProductServiceMock->expects($this->once())
            ->method('createProduct')
            ->with([], $websiteId)
            ->willReturn($platformProductMock);

        $this->platformProductServiceMock->expects($this->once())
            ->method('saveProduct')
            ->with($platformProductMock, $websiteId)
            ->willReturn($platformProductMock);

        $this->platformProductStorageMock->expects($this->once())
            ->method('save')
            ->with($platformProductMock, $websiteId);

        $this->assertSame(
            $platformProductMock,
            $this->productManager->saveProduct($productMock, $websiteId)
        );
    }

    public function testSaveCachedProduct()
    {
        $sku = 'sku';
        $websiteId = 23;

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())->method('setSku')->with($sku)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setPrice')->with(100)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setName')->with('product name')->willReturnSelf();

        $productMock = $this->getMockBuilder(ProductInterface::class)->getMock();
        $productMock->expects($this->any())->method('getSku')->willReturn($sku);
        $productMock->expects($this->any())->method('getPrice')->willReturn(100);
        $productMock->expects($this->any())->method('getName')->willReturn('product name');

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn($platformProductMock);

        $this->platformProductStorageMock->expects($this->once())
            ->method('remove')
            ->with($sku, $websiteId);

        $this->platformProductServiceMock->expects($this->never())->method('loadProducts');
        $this->platformProductServiceMock->expects($this->once())
            ->method('saveProduct')
            ->with($platformProductMock, $websiteId)
            ->willReturn($platformProductMock);

        $this->platformProductStorageMock->expects($this->once())
            ->method('save')
            ->with($platformProductMock, $websiteId);

        $this->assertSame(
            $platformProductMock,
            $this->productManager->saveProduct($productMock, $websiteId)
        );
    }

    public function testSaveProduct()
    {
        $sku = 'sku';
        $websiteId = 23;

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())->method('setSku')->with($sku)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setPrice')->with(100)->willReturnSelf();
        $platformProductMock->expects($this->once())->method('setName')->with('product name')->willReturnSelf();

        $productMock = $this->getMockBuilder(ProductInterface::class)->getMock();
        $productMock->expects($this->any())->method('getSku')->willReturn($sku);
        $productMock->expects($this->any())->method('getPrice')->willReturn(100);
        $productMock->expects($this->any())->method('getName')->willReturn('product name');

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn(null);
        $this->platformProductStorageMock->expects($this->once())
            ->method('remove')
            ->with($sku, $websiteId);

        $this->platformProductServiceMock->expects($this->once())
            ->method('loadProducts')
            ->with($sku, $websiteId)
            ->willReturn([$platformProductMock]);

        $this->platformProductServiceMock->expects($this->never())->method('createProduct');

        $this->platformProductServiceMock->expects($this->once())
            ->method('saveProduct')
            ->with($platformProductMock, $websiteId)
            ->willReturn($platformProductMock);

        $this->platformProductStorageMock->expects($this->once())
            ->method('save')
            ->with($platformProductMock, $websiteId);

        $this->assertSame(
            $platformProductMock,
            $this->productManager->saveProduct($productMock, $websiteId)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createPlatformProductMock()
    {
        return $this->getMockBuilder(PlatformProductInterface::class)->getMock();
    }
}
