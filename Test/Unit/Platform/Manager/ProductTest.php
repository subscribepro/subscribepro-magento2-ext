<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Manager;

use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Platform\Manager\Product;
use Swarming\SubscribePro\Platform\Service\Product as ProductService;
use Swarming\SubscribePro\Platform\Storage\Product as ProductStorage;
use Magento\Catalog\Api\Data\ProductInterface as MagentoProductInterface;

class ProductTest extends \PHPUnit_Framework_TestCase
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

    protected function setUp()
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
        $productMock = $this->createProductMock();

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn($productMock);

        $this->platformProductServiceMock->expects($this->never())->method('loadProducts');
        $this->platformProductStorageMock->expects($this->never())->method('save');

        $this->assertSame($productMock, $this->productManager->getProduct($sku, $websiteId));
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
        $productMock = $this->createProductMock();

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn(null);

        $this->platformProductServiceMock->expects($this->once())
            ->method('loadProducts')
            ->with($sku, $websiteId)
            ->willReturn([$productMock]);

        $this->platformProductStorageMock->expects($this->once())
            ->method('save')
            ->with($productMock, $websiteId);

        $this->assertSame($productMock, $this->productManager->getProduct($sku, $websiteId));
    }

    public function testSaveNewProduct()
    {
        $sku = 'sku';
        $websiteId = 23;

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('setSku')->with($sku)->willReturnSelf();
        $productMock->expects($this->once())->method('setPrice')->with(100)->willReturnSelf();
        $productMock->expects($this->once())->method('setName')->with('product name')->willReturnSelf();

        $magentoProduct = $this->getMockBuilder(MagentoProductInterface::class)->getMock();
        $magentoProduct->expects($this->any())->method('getSku')->willReturn($sku);
        $magentoProduct->expects($this->any())->method('getPrice')->willReturn(100);
        $magentoProduct->expects($this->any())->method('getName')->willReturn('product name');

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
            ->willReturn($productMock);

        $this->platformProductServiceMock->expects($this->once())
            ->method('saveProduct')
            ->with($productMock, $websiteId)
            ->willReturn($productMock);

        $this->platformProductStorageMock->expects($this->once())
            ->method('save')
            ->with($productMock, $websiteId);

        $this->assertSame(
            $productMock,
            $this->productManager->saveProduct($magentoProduct, $websiteId)
        );
    }

    public function testSaveCachedProduct()
    {
        $sku = 'sku';
        $websiteId = 23;

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('setSku')->with($sku)->willReturnSelf();
        $productMock->expects($this->once())->method('setPrice')->with(100)->willReturnSelf();
        $productMock->expects($this->once())->method('setName')->with('product name')->willReturnSelf();

        $magentoProduct = $this->getMockBuilder(MagentoProductInterface::class)->getMock();
        $magentoProduct->expects($this->any())->method('getSku')->willReturn($sku);
        $magentoProduct->expects($this->any())->method('getPrice')->willReturn(100);
        $magentoProduct->expects($this->any())->method('getName')->willReturn('product name');

        $this->platformProductStorageMock->expects($this->once())
            ->method('load')
            ->with($sku, $websiteId)
            ->willReturn($productMock);

        $this->platformProductStorageMock->expects($this->once())
            ->method('remove')
            ->with($sku, $websiteId);

        $this->platformProductServiceMock->expects($this->never())->method('loadProducts');
        $this->platformProductServiceMock->expects($this->once())
            ->method('saveProduct')
            ->with($productMock, $websiteId)
            ->willReturn($productMock);

        $this->platformProductStorageMock->expects($this->once())
            ->method('save')
            ->with($productMock, $websiteId);

        $this->assertSame(
            $productMock,
            $this->productManager->saveProduct($magentoProduct, $websiteId)
        );
    }

    public function testSaveProduct()
    {
        $sku = 'sku';
        $websiteId = 23;

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('setSku')->with($sku)->willReturnSelf();
        $productMock->expects($this->once())->method('setPrice')->with(100)->willReturnSelf();
        $productMock->expects($this->once())->method('setName')->with('product name')->willReturnSelf();

        $magentoProduct = $this->getMockBuilder(MagentoProductInterface::class)->getMock();
        $magentoProduct->expects($this->any())->method('getSku')->willReturn($sku);
        $magentoProduct->expects($this->any())->method('getPrice')->willReturn(100);
        $magentoProduct->expects($this->any())->method('getName')->willReturn('product name');

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
            ->willReturn([$productMock]);

        $this->platformProductServiceMock->expects($this->never())->method('createProduct');

        $this->platformProductServiceMock->expects($this->once())
            ->method('saveProduct')
            ->with($productMock, $websiteId)
            ->willReturn($productMock);

        $this->platformProductStorageMock->expects($this->once())
            ->method('save')
            ->with($productMock, $websiteId);

        $this->assertSame(
            $productMock,
            $this->productManager->saveProduct($magentoProduct, $websiteId)
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
