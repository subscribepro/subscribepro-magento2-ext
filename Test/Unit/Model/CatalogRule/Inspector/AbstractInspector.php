<?php

namespace Swarming\SubscribePro\Test\Unit\Model\CatalogRule\Inspector;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\CatalogRule\Observer\RulePricesStorage;
use Magento\Catalog\Model\Product;
use DateTime;
use Magento\Catalog\Model\Product\Type\Price as PriceModel;

abstract class AbstractInspector extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeDate;

    /**
     * @var \Magento\CatalogRule\Observer\RulePricesStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rulePricesStorage;

    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();

        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->rulePricesStorage = $this->getMockBuilder(RulePricesStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param float $rulePrice
     * @param string $dateString
     * @param int $websiteId
     * @param int $customerGroupId
     * @param int $productId
     */
    protected function prepareRulePriceStorage($rulePrice, $dateString, $websiteId, $customerGroupId, $productId)
    {
        $this->rulePricesStorage->expects($this->once())
            ->method('getRulePrice')
            ->with("{$dateString}|{$websiteId}|{$customerGroupId}|{$productId}")
            ->willReturn($rulePrice);
    }

    /**
     * @param float $price
     * @param float $basePrice
     * @return \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareProductMockWithSpecialPrice($price, $basePrice)
    {
        $priceModel = $this->getMockBuilder(PriceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $priceModel->expects($this->once())
            ->method('getBasePrice')
            ->willReturn($basePrice);

        $product = $this->createProductMock();
        $product->expects($this->once())->method('getPrice')->willReturn($price);
        $product->expects($this->once())->method('getPriceModel')->willReturn($priceModel);

        $product->expects($this->never())->method('getId');
        $product->expects($this->never())->method('hasCustomerGroupId');
        $product->expects($this->never())->method('getCustomerGroupId');
        $product->expects($this->never())->method('getStoreId');

        return $product;
    }

    /**
     * @param float $price
     * @param int $productId
     * @param int $customerGroupId
     * @param int $storeId
     * @return \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareProductMock($price, $productId, $customerGroupId, $storeId)
    {
        $priceModel = $this->getMockBuilder(PriceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $priceModel->expects($this->once())
            ->method('getBasePrice')
            ->willReturn($price);

        $product = $this->createProductMock();

        $product->expects($this->once())->method('getPrice')->willReturn($price);
        $product->expects($this->once())->method('getPriceModel')->willReturn($priceModel);

        $product->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($productId);

        $product->expects($this->atLeastOnce())
            ->method('hasCustomerGroupId')
            ->willReturn((bool)$customerGroupId);

        if ($customerGroupId) {
            $product->expects($this->atLeastOnce())
                ->method('getCustomerGroupId')
                ->willReturn($customerGroupId);
        } else {
            $product->expects($this->never())
                ->method('getCustomerGroupId');
        }

        $product->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn($storeId);

        return $product;
    }

    /**
     * @param int $customerGroupId
     */
    protected function prepareCustomerSession($customerGroupId)
    {
        $this->customerSession->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);
    }

    /**
     * @param string $dateString
     * @param int $storeId
     * @return DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareDateMock($dateString, $storeId)
    {
        $date = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $date->expects($this->atLeastOnce())
            ->method('format')
            ->with('Y-m-d H:i:s')
            ->willReturn($dateString);

        $this->localeDate->expects($this->atLeastOnce())
            ->method('scopeDate')
            ->with($storeId)
            ->willReturn($date);

        return $date;
    }

    /**
     * @param int $storeId
     * @param int $websiteId
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\StoreInterface
     */
    protected function prepareStoreMock($storeId, $websiteId)
    {
        $store = $this->getMockBuilder(StoreInterface::class)
            ->getMock();
        $store->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->storeManager->expects($this->atLeastOnce())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($store);

        return $store;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    protected function createProductMock()
    {
        return $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'getStoreId',
                'hasCustomerGroupId',
                'getCustomerGroupId',
                'getCustomOption',
                '__wakeup',
                'getPrice',
                'getPriceModel'
            ])
            ->getMock();
    }
}
