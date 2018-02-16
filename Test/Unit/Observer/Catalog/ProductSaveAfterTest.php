<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\Catalog;

use GuzzleHttp\Psr7\Response;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Observer\Catalog\ProductSaveAfter;
use Swarming\SubscribePro\Model\Config\General as GeneralConfig;
use Swarming\SubscribePro\Platform\Manager\Product as ProductManager;
use Swarming\SubscribePro\Helper\Product as ProductHelper;

class ProductSaveAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\Catalog\ProductSaveAfter
     */
    protected $productSaveAfter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Product
     */
    protected $productHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    protected function setUp()
    {
        $this->generalConfigMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->platformProductManagerMock = $this->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->productSaveAfter = new ProductSaveAfter(
            $this->generalConfigMock,
            $this->storeManagerMock,
            $this->productRepositoryMock,
            $this->platformProductManagerMock,
            $this->productHelperMock,
            $this->loggerMock
        );
    }

    public function testExecuteIfGroupedProduct()
    {
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE);

        $this->storeManagerMock->expects($this->never())->method('getWebsites');
        $this->platformProductManagerMock->expects($this->never())->method('saveProduct');

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($productMock);

        $this->productSaveAfter->execute($observerMock);
    }

    public function testExecuteIfSubscribeProNotEnabled()
    {
        $website1Id = 413121;
        $website1Code = 'code';
        $website1Mock = $this->createWebsiteMock();
        $website1Mock->expects($this->once())->method('getCode')->willReturn($website1Code);

        $website2Id = 222;
        $website2Mock = $this->createWebsiteMock();

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($website1Id);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($productMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->with(false)
            ->willReturn([
                $website1Id => $website1Mock,
                $website2Id => $website2Mock
            ]);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($website1Code)
            ->willReturn(false);

        $this->platformProductManagerMock->expects($this->never())->method('saveProduct');

        $this->productSaveAfter->execute($observerMock);
    }

    public function testExecuteIfStoreGroupNotFound()
    {
        $group1Id = 131;
        $website1Id = 413121;
        $website1Code = 'code';
        $website1Mock = $this->createWebsiteMock();
        $website1Mock->expects($this->once())->method('getCode')->willReturn($website1Code);
        $website1Mock->expects($this->once())->method('getDefaultGroupId')->willReturn($group1Id);

        $website2Id = 222;
        $website2Mock = $this->createWebsiteMock();

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($website1Id);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($productMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->with(false)
            ->willReturn([
                $website1Id => $website1Mock,
                $website2Id => $website2Mock
            ]);
        $this->storeManagerMock->expects($this->once())
            ->method('getGroup')
            ->with($group1Id)
            ->willReturn(null);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($website1Code)
            ->willReturn(true);

        $this->platformProductManagerMock->expects($this->never())->method('saveProduct');

        $this->productSaveAfter->execute($observerMock);
    }

    public function testExecuteIfStoreNotFound()
    {
        $group1Id = 131;
        $website1Id = 413121;
        $website1Code = 'code';
        $website1Mock = $this->createWebsiteMock();
        $website1Mock->expects($this->once())->method('getCode')->willReturn($website1Code);
        $website1Mock->expects($this->once())->method('getDefaultGroupId')->willReturn($group1Id);

        $website2Id = 222;
        $website2Mock = $this->createWebsiteMock();

        $groupMock = $this->createGroupMock();
        $groupMock->expects($this->once())->method('getDefaultStoreId')->willReturn(null);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($website1Id);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($productMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->with(false)
            ->willReturn([
                $website1Id => $website1Mock,
                $website2Id => $website2Mock
            ]);
        $this->storeManagerMock->expects($this->once())
            ->method('getGroup')
            ->with($group1Id)
            ->willReturn($groupMock);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($website1Code)
            ->willReturn(true);

        $this->platformProductManagerMock->expects($this->never())->method('saveProduct');

        $this->productSaveAfter->execute($observerMock);
    }

    public function testExecuteIfProductNotFound()
    {
        $store1Id = 567;
        $group1Id = 131;
        $website1Id = 413121;
        $website1Code = 'code';
        $website1Mock = $this->createWebsiteMock();
        $website1Mock->expects($this->once())->method('getCode')->willReturn($website1Code);
        $website1Mock->expects($this->once())->method('getDefaultGroupId')->willReturn($group1Id);

        $website2Id = 222;
        $website2Mock = $this->createWebsiteMock();

        $groupMock = $this->createGroupMock();
        $groupMock->expects($this->once())->method('getDefaultStoreId')->willReturn($store1Id);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($website1Id);

        $sku1 = 'sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $productMock->expects($this->once())->method('getSku')->willReturn($sku1);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($productMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->with(false)
            ->willReturn([
                $website1Id => $website1Mock,
                $website2Id => $website2Mock
            ]);
        $this->storeManagerMock->expects($this->once())
            ->method('getGroup')
            ->with($group1Id)
            ->willReturn($groupMock);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($website1Code)
            ->willReturn(true);

        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($sku1, false, $store1Id)
            ->willReturn(null);

        $this->platformProductManagerMock->expects($this->never())->method('saveProduct');

        $this->productSaveAfter->execute($observerMock);
    }

    public function testExecuteIfNotSubscriptionProduct()
    {
        $store1Id = 567;
        $group1Id = 131;
        $website1Id = 413121;
        $website1Code = 'code';
        $website1Mock = $this->createWebsiteMock();
        $website1Mock->expects($this->once())->method('getCode')->willReturn($website1Code);
        $website1Mock->expects($this->once())->method('getDefaultGroupId')->willReturn($group1Id);

        $website2Id = 222;
        $website2Mock = $this->createWebsiteMock();

        $groupMock = $this->createGroupMock();
        $groupMock->expects($this->once())->method('getDefaultStoreId')->willReturn($store1Id);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($website1Id);

        $sku1 = 'sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $productMock->expects($this->once())->method('getSku')->willReturn($sku1);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($productMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->with(false)
            ->willReturn([
                $website1Id => $website1Mock,
                $website2Id => $website2Mock
            ]);
        $this->storeManagerMock->expects($this->once())
            ->method('getGroup')
            ->with($group1Id)
            ->willReturn($groupMock);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($website1Code)
            ->willReturn(true);

        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($sku1, false, $store1Id)
            ->willReturn($productMock);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(false);

        $this->platformProductManagerMock->expects($this->never())->method('saveProduct');

        $this->productSaveAfter->execute($observerMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Fail to save product on Subscribe Pro platform for website "website name".
     */
    public function testExecuteIfFailToSaveProduct()
    {
        $exception = new \SubscribePro\Exception\HttpException(new Response(404));

        $store1Id = 567;
        $group1Id = 131;
        $website1Id = 413121;
        $website1Code = 'code';
        $website1Mock = $this->createWebsiteMock();
        $website1Mock->expects($this->once())->method('getCode')->willReturn($website1Code);
        $website1Mock->expects($this->once())->method('getDefaultGroupId')->willReturn($group1Id);
        $website1Mock->expects($this->once())->method('getId')->willReturn($website1Id);
        $website1Mock->expects($this->once())->method('getName')->willReturn('website name');

        $website2Id = 222;
        $website2Mock = $this->createWebsiteMock();

        $groupMock = $this->createGroupMock();
        $groupMock->expects($this->once())->method('getDefaultStoreId')->willReturn($store1Id);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($website1Id);

        $sku1 = 'sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $productMock->expects($this->once())->method('getSku')->willReturn($sku1);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($productMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->with(false)
            ->willReturn([
                $website1Id => $website1Mock,
                $website2Id => $website2Mock
            ]);
        $this->storeManagerMock->expects($this->once())
            ->method('getGroup')
            ->with($group1Id)
            ->willReturn($groupMock);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($website1Code)
            ->willReturn(true);

        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($sku1, false, $store1Id)
            ->willReturn($productMock);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(true);

        $this->platformProductManagerMock->expects($this->once())
            ->method('saveProduct')
            ->with($productMock, $website1Id)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->productSaveAfter->execute($observerMock);
    }

    public function testExecute()
    {
        $store1Id = 567;
        $group1Id = 131;
        $website1Id = 413121;
        $website1Code = 'code';
        $website1Mock = $this->createWebsiteMock();
        $website1Mock->expects($this->once())->method('getCode')->willReturn($website1Code);
        $website1Mock->expects($this->once())->method('getDefaultGroupId')->willReturn($group1Id);
        $website1Mock->expects($this->once())->method('getId')->willReturn($website1Id);

        $website2Id = 222;
        $website2Mock = $this->createWebsiteMock();

        $groupMock = $this->createGroupMock();
        $groupMock->expects($this->once())->method('getDefaultStoreId')->willReturn($store1Id);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($website1Id);

        $sku1 = 'sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $productMock->expects($this->once())->method('getSku')->willReturn($sku1);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($productMock);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->with(false)
            ->willReturn([
                $website1Id => $website1Mock,
                $website2Id => $website2Mock
            ]);
        $this->storeManagerMock->expects($this->once())
            ->method('getGroup')
            ->with($group1Id)
            ->willReturn($groupMock);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->with($website1Code)
            ->willReturn(true);

        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($sku1, false, $store1Id)
            ->willReturn($productMock);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(true);

        $this->platformProductManagerMock->expects($this->once())
            ->method('saveProduct')
            ->with($productMock, $website1Id);

        $this->productSaveAfter->execute($observerMock);
    }

    public function testExecuteWithMultipleWebsites()
    {
        $store1Id = 567;
        $group1Id = 131;
        $website1Id = 413121;
        $website1Code = 'code';
        $website1Mock = $this->createWebsiteMock();
        $website1Mock->expects($this->once())->method('getCode')->willReturn($website1Code);
        $website1Mock->expects($this->once())->method('getDefaultGroupId')->willReturn($group1Id);
        $website1Mock->expects($this->once())->method('getId')->willReturn($website1Id);

        $store2Id = 123;
        $group2Id = 4345;
        $website2Id = 7567;
        $website2Code = 'website_2_code';
        $website2Mock = $this->createWebsiteMock();
        $website2Mock->expects($this->once())->method('getCode')->willReturn($website2Code);
        $website2Mock->expects($this->once())->method('getDefaultGroupId')->willReturn($group2Id);
        $website2Mock->expects($this->once())->method('getId')->willReturn($website2Id);

        $group1Mock = $this->createGroupMock();
        $group1Mock->expects($this->once())->method('getDefaultStoreId')->willReturn($store1Id);

        $group2Mock = $this->createGroupMock();
        $group2Mock->expects($this->once())->method('getDefaultStoreId')->willReturn($store2Id);

        $storeMock = $this->createStoreMock();
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn(null);

        $sku = 'sku';
        $product1Mock = $this->createProductMock();
        $product1Mock->expects($this->once())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $product1Mock->expects($this->any())->method('getSku')->willReturn($sku);

        $product2Mock = $this->createProductMock();

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('product')
            ->willReturn($product1Mock);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->with(false)
            ->willReturn([
                $website1Id => $website1Mock,
                $website2Id => $website2Mock
            ]);
        $this->storeManagerMock->expects($this->at(2))
            ->method('getGroup')
            ->with($group1Id)
            ->willReturn($group1Mock);
        $this->storeManagerMock->expects($this->at(3))
            ->method('getGroup')
            ->with($group2Id)
            ->willReturn($group2Mock);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $this->generalConfigMock->expects($this->at(0))
            ->method('isEnabled')
            ->with($website1Code)
            ->willReturn(true);
        $this->generalConfigMock->expects($this->at(1))
            ->method('isEnabled')
            ->with($website2Code)
            ->willReturn(true);

        $this->productRepositoryMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [$sku, false, $store1Id, false, $product1Mock],
                [$sku, false, $store2Id, false, $product2Mock]
            ]);

        $this->productHelperMock->expects($this->exactly(2))
            ->method('isSubscriptionEnabled')
            ->willReturnMap([[$product1Mock, true], [$product2Mock, true]]);

        $this->platformProductManagerMock->expects($this->at(0))
            ->method('saveProduct')
            ->with($product1Mock, $website1Id);
        $this->platformProductManagerMock->expects($this->at(1))
            ->method('saveProduct')
            ->with($product2Mock, $website2Id);

        $this->productSaveAfter->execute($observerMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\Store
     */
    private function createStoreMock()
    {
        return $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\WebsiteInterface
     */
    private function createWebsiteMock()
    {
        return $this->getMockBuilder(WebsiteInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\GroupInterface
     */
    private function createGroupMock()
    {
        return $this->getMockBuilder(GroupInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\Data\ProductInterface
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)->getMock();
    }
}
