<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Storage;

use Magento\Framework\App\Cache\StateInterface as CacheStateInterface;
use Magento\Framework\Cache\FrontendInterface as CacheFrontendInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;
use Swarming\SubscribePro\Api\Data\ProductInterfaceFactory as PlatformProductFactory;
use Swarming\SubscribePro\Model\Config\Advanced as CacheConfig;
use Swarming\SubscribePro\Platform\Cache\Type\Product;
use Swarming\SubscribePro\Platform\Storage\Product as ProductStorage;

class ProductTest extends TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Storage\Product
     */
    protected $productStorage;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface|MockObject
     */
    protected $cacheMock;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface|MockObject
     */
    protected $stateMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Advanced|MockObject
     */
    protected $advancedConfigMock;

    /**
     * @var \Swarming\SubscribePro\Api\Data\ProductInterfaceFactory|MockObject
     */
    protected $platformProductFactoryMock;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|MockObject
     */
    protected $serializerMock;

    protected function setUp(): void
    {
        $this->cacheMock = $this->getMockBuilder(CacheFrontendInterface::class)->getMock();
        $this->stateMock = $this->getMockBuilder(CacheStateInterface::class)->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->advancedConfigMock = $this->getMockBuilder(CacheConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformProductFactoryMock = $this->getMockBuilder(PlatformProductFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMock();

        $this->productStorage = new ProductStorage(
            $this->cacheMock,
            $this->stateMock,
            $this->advancedConfigMock,
            $this->storeManagerMock,
            $this->platformProductFactoryMock,
            $this->serializerMock
        );
    }

    public function testLoadIfProductCacheDisabled(): void
    {
        $sku = 'sku16';
        $websiteId = 1;
        $websiteCode = 'code1';

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')->willReturn($websiteCode);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->cacheMock->expects(self::never())->method('load');

        self::assertNull($this->productStorage->load($sku, $websiteId));
    }

    public function testLoadIfProductCacheNotLoaded(): void
    {
        $sku = 'sku43';
        $websiteId = 13;
        $websiteCode = 'code13';

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects(self::once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));

        $this->cacheMock->expects(self::once())
            ->method('load')
            ->with(self::stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'))
            ->willReturn(null);

        self::assertNull($this->productStorage->load($sku, $websiteId));
    }

    public function testLoad(): void
    {
        $sku = 'sku61';
        $websiteId = 21;
        $websiteCode = 'code21';
        $platformProductMock = $this->createPlatformProductMock();
        $platformProductData = ['sku' => $sku];
        $platformProductDataSerialized = json_encode($platformProductData);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')
            ->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::exactly(2))
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->serializerMock->expects(self::exactly(2))
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));

        $this->cacheMock->expects(self::once())
            ->method('load')
            ->with(self::stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'))
            ->willReturn($platformProductDataSerialized);

        $this->serializerMock->expects(self::once())
            ->method('unserialize')
            ->with($platformProductDataSerialized)
            ->willReturn($platformProductData);

        $this->platformProductFactoryMock->expects(self::once())
            ->method('create')
            ->with(['data' => $platformProductData])
            ->willReturn($platformProductMock);

        $cachedProduct = $this->productStorage->load($sku, $websiteId);

        self::assertEquals(
            $platformProductMock,
            $cachedProduct,
            'Fail to test product storage load from cache'
        );
        self::assertSame(
            $cachedProduct,
            $this->productStorage->load($sku, $websiteId),
            'Fail to test product storage load from internal cache'
        );
    }

    public function testSaveIfProductCacheDisabled(): void
    {
        $sku = 'sku25';
        $websiteId = 89;
        $websiteCode = 'code89';

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects(self::once())
            ->method('getSku')
            ->willReturn($sku);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')
            ->willReturn($websiteCode);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));
        $this->cacheMock->expects(self::never())->method('save');

        $this->productStorage->save($platformProductMock, $websiteId);
    }

    public function testSaveWithoutLifeTime(): void
    {
        $sku = 'sku99';
        $websiteId = 90;
        $websiteCode = 'code90';
        $lifeTime = 9999;
        $platformProductData = ['sku' => $sku];
        $platformProductDataSerialized = json_encode($platformProductData);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects(self::once())
            ->method('getSku')
            ->willReturn($sku);
        $platformProductMock->expects(self::once())
            ->method('toArray')
            ->willReturn($platformProductData);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')
            ->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->advancedConfigMock->expects(self::once())
            ->method('getCacheLifeTime')
            ->with($websiteId)
            ->willReturn($lifeTime);

        $this->serializerMock->expects(self::at(0))
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));

        $this->serializerMock->expects(self::at(1))
            ->method('serialize')
            ->with($platformProductData)
            ->willReturn($platformProductDataSerialized);

        $this->cacheMock->expects(self::once())
            ->method('save')
            ->with(
                $platformProductDataSerialized,
                self::stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'),
                [],
                $lifeTime
            );

        $this->productStorage->save($platformProductMock, $websiteId);
    }

    public function testSaveWithLifeTime(): void
    {
        $sku = 'sku82';
        $websiteId = 21;
        $websiteCode = 'code21';
        $lifeTime = 8888;
        $platformProductData = ['sku' => $sku];
        $platformProductDataSerialized = json_encode($platformProductData);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects(self::once())
            ->method('getSku')
            ->willReturn($sku);
        $platformProductMock->expects(self::once())
            ->method('toArray')
            ->willReturn($platformProductData);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')
            ->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->advancedConfigMock->expects(self::never())->method('getCacheLifeTime');

        $this->serializerMock->expects(self::at(0))
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));

        $this->serializerMock->expects(self::at(1))
            ->method('serialize')
            ->with($platformProductData)
            ->willReturn($platformProductDataSerialized);

        $this->cacheMock->expects(self::once())
            ->method('save')
            ->with(
                $platformProductDataSerialized,
                self::stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'),
                [],
                $lifeTime
            );

        $this->productStorage->save($platformProductMock, $websiteId, $lifeTime);
    }

    public function testRemoveIfProductCacheDisabled(): void
    {
        $sku = 'sku2';
        $websiteId = 59;
        $websiteCode = 'code59';

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')
            ->willReturn($websiteCode);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->cacheMock->expects(self::never())->method('remove');

        $this->productStorage->remove($sku, $websiteId);
    }

    public function testRemoveIfProductCachedInternal(): void
    {
        $sku = 'sku79';
        $websiteId = 40;
        $websiteCode = 'code40';

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')
            ->willReturn($websiteCode);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects(self::once())
            ->method('getSku')
            ->willReturn($sku);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects(self::exactly(2))
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->storeManagerMock->expects(self::exactly(2))
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->serializerMock->expects(self::exactly(2))
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));

        $this->cacheMock->expects(self::never())->method('remove');

        $this->productStorage->save($platformProductMock, $websiteId);
        $this->productStorage->remove($sku, $websiteId);
    }

    public function testRemove(): void
    {
        $sku = 'sku77';
        $websiteId = 11;
        $websiteCode = 'code11';
        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->with([$sku, $websiteCode])
            ->willReturn(json_encode([$sku, $websiteCode]));

        $this->cacheMock->expects(self::once())
            ->method('remove')
            ->with(self::stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'));

        $this->productStorage->remove($sku, $websiteId);
    }

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPlatformProductMock(): MockObject
    {
        return $this->getMockBuilder(PlatformProductInterface::class)->getMock();
    }

    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createWebsiteMock(): MockObject
    {
        return $this->getMockBuilder(WebsiteInterface::class)->getMock();
    }
}
