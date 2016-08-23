<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Storage;

use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Platform\Storage\Product as ProductStorage;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Cache\FrontendInterface as CacheFrontendInterface;
use Magento\Framework\App\Cache\StateInterface as CacheStateInterface;
use Swarming\SubscribePro\Model\Config\Cache as CacheConfig;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Storage\Product
     */
    protected $productStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Cache\FrontendInterface
     */
    protected $cacheMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Cache\StateInterface
     */
    protected $stateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\Cache
     */
    protected $cacheConfigMock;

    protected function setUp()
    {
        $this->cacheMock = $this->getMockBuilder(CacheFrontendInterface::class)->getMock();
        $this->stateMock = $this->getMockBuilder(CacheStateInterface::class)->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->cacheConfigMock = $this->getMockBuilder(CacheConfig::class)
            ->disableOriginalConstructor()->getMock();

        $this->productStorage = new ProductStorage(
            $this->cacheMock,
            $this->stateMock,
            $this->cacheConfigMock,
            $this->storeManagerMock
        );
    }

    public function testLoadIfProductCacheDisabled()
    {
        $sku = 'sku';
        $websiteId = 23;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Product::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->cacheMock->expects($this->never())->method('load');

        $this->assertNull($this->productStorage->load($sku, $websiteId));
    }

    public function testLoadIfProductCacheNotLoaded()
    {
        $sku = 'sku';
        $websiteId = 23;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())->method('getCode')->willReturn('code');

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($this->stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'))
            ->willReturn(null);

        $this->assertNull($this->productStorage->load($sku, $websiteId));
    }

    public function testLoad()
    {
        $sku = 'sku';
        $websiteId = 23;
        $productMock = $this->createProductMock();

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->exactly(2))
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($this->stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'))
            ->willReturn(serialize($productMock));

        $cachedProduct = $this->productStorage->load($sku, $websiteId);

        $this->assertEquals(
            $productMock,
            $cachedProduct,
            'Fail to test product storage load from cache'
        );
        $this->assertSame(
            $cachedProduct,
            $this->productStorage->load($sku, $websiteId),
            'Fail to test product storage load from internal cache'
        );
    }

    public function testSaveIfProductCacheDisabled()
    {
        $websiteId = 23;

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getSku')->willReturn('sku');

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Product::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->cacheMock->expects($this->never())->method('save');

        $this->productStorage->save($productMock, $websiteId);
    }

    public function testSaveWithoutLifeTime()
    {
        $websiteId = 5002;
        $lifeTime = 5005;

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getSku')->willReturn('sku');

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheConfigMock->expects($this->once())
            ->method('getCacheLifeTime')
            ->with($websiteId)
            ->willReturn($lifeTime);

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with(
                $this->isType('string'),
                $this->stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'),
                [],
                $lifeTime
            );

        $this->productStorage->save($productMock, $websiteId);
    }

    public function testSaveWithLifeTime()
    {
        $websiteId = 2020;
        $lifeTime = 1010;

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getSku')->willReturn('sku');

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheConfigMock->expects($this->never())->method('getCacheLifeTime');

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with(
                $this->isType('string'),
                $this->stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'),
                [],
                $lifeTime
            );

        $this->productStorage->save($productMock, $websiteId, $lifeTime);
    }

    public function testRemoveIfProductCacheDisabled()
    {
        $sku = 'sku2';
        $websiteId = 3232;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Product::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->cacheMock->expects($this->never())->method('remove');

        $this->productStorage->remove($sku, $websiteId);
    }

    public function testRemove()
    {
        $websiteId = 12323;
        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Product::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheMock->expects($this->once())
            ->method('remove')
            ->with($this->stringContains(ProductStorage::PRODUCT_CACHE_KEY . '_'));

        $this->productStorage->remove('sku', $websiteId);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\WebsiteInterface
     */
    private function createWebsiteMock()
    {
        return $this->getMockBuilder(WebsiteInterface::class)->getMock();
    }
}
