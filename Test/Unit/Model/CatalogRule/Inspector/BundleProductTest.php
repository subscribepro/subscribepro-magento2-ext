<?php

namespace Swarming\SubscribePro\Test\Unit\Model\CatalogRule\Inspector;

use Swarming\SubscribePro\Model\CatalogRule\Inspector\BundleProduct;
use Magento\Catalog\Model\Product\Configuration\Item\Option as ProductConfigurationOption;

class BundleProductTest extends AbstractInspector
{
    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\Inspector\BundleProduct
     */
    protected $bundleProduct;

    protected function setUp()
    {
        parent::setUp();

        $this->bundleProduct = new BundleProduct(
            $this->customerSession,
            $this->storeManager,
            $this->localeDate,
            $this->rulePricesStorage
        );
    }

    public function testIsAppliedIfHasSpecialPrice() {
        $price = 100;
        $basePrice = 110;

        $product = $this->prepareProductMockWithSpecialPrice($price, $basePrice);

        $this->rulePricesStorage->expects($this->never())->method('getRulePrice');

        $this->assertTrue($this->bundleProduct->isApplied($product));
    }

    /**
     * @param bool $isApplied
     * @param int $rulePrice
     * @param string $productId
     * @param bool $customerGroupId
     * @param string $sessionCustomerGroupId
     * @param string $storeId
     * @param string $websiteId
     * @param float $price
     * @param string $dateString
     * @dataProvider isAppliedDataProvider
     */
    public function testIsApplied(
        $isApplied,
        $rulePrice,
        $productId,
        $customerGroupId,
        $sessionCustomerGroupId,
        $storeId,
        $websiteId,
        $price,
        $dateString
    ) {
        $this->prepareDateMock($dateString, $storeId);

        $this->prepareStoreMock($storeId, $websiteId);

        $product = $this->prepareProductMock($price, $productId, $customerGroupId, $storeId);
        $product->expects($this->any())
            ->method('getCustomOption')
            ->with('bundle_selection_ids')
            ->willReturn(false);

        if (!$customerGroupId) {
            $this->prepareCustomerSession($sessionCustomerGroupId);
        }

        $customerGroupId = $customerGroupId ?: $sessionCustomerGroupId;
        $this->prepareRulePriceStorage($rulePrice, $dateString, $websiteId, $customerGroupId, $productId);

        $this->assertEquals($isApplied, $this->bundleProduct->isApplied($product));
    }

    /**
     * @return array
     */
    public function isAppliedDataProvider()
    {
        return [
            'applied' => [
                'isApplied' => true,
                'rulePrice' => '33.3',
                'productId' => 54,
                'customerGroupId' => 2,
                'sessionCustomerGroupId' => 2,
                'storeId' => 3,
                'websiteId' => 1,
                'price' => 100,
                'dateString' => 'date_format'
            ],
            'not applied' => [
                'isApplied' => false,
                'rulePrice' => false,
                'productId' => 33,
                'customerGroupId' => 1,
                'sessionCustomerGroupId' => 1,
                'storeId' => 2,
                'websiteId' => 5,
                'price' => 120,
                'dateString' => 'date_format_3'
            ],
            'not applied with 0. rule price' => [
                'isApplied' => false,
                'rulePrice' => '0.0',
                'productId' => 1,
                'customerGroupId' => 1,
                'sessionCustomerGroupId' => 1,
                'storeId' => 1,
                'websiteId' => 1,
                'price' => 10,
                'dateString' => 'date_format_4'
            ],
        ];
    }

    /**
     * @param bool $isApplied
     * @param int $parentProductId
     * @param [] $selectionIds
     * @param [] $rulePrices
     * @param [] $prices
     * @param [] $productIds
     * @param bool $customerGroupId
     * @param float $parentPrice
     * @param string $storeId
     * @param string $websiteId
     * @param string $dateString
     * @internal param $ [] $rulePrice
     * @internal param $ [] $productId
     * @dataProvider isAppliedWithChildProductsDataProvider
     */
    public function testIsAppliedWithChildProducts(
        $isApplied,
        $parentProductId,
        $selectionIds,
        $rulePrices,
        $prices,
        $productIds,
        $customerGroupId,
        $parentPrice,
        $storeId,
        $websiteId,
        $dateString
    ) {
        $this->prepareDateMock($dateString, $storeId);

        $this->prepareStoreMock($storeId, $websiteId);

        $childProducts = [];
        foreach (array_keys($productIds) as $key) {
            $childProducts[] = $this->prepareProductMock($prices[$key], $productIds[$key], $customerGroupId, $storeId);
        }

        $option = $this->getMockBuilder(ProductConfigurationOption::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $option->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn(json_encode($selectionIds));

        $sections = [];
        foreach (array_keys($selectionIds) as $selectionKey) {
            $section = $this->createSectionMock();
            $section->expects($this->atLeastOnce())
                ->method('getProduct')
                ->willReturn($childProducts[$selectionKey]);

            $sections[] = $section;
        }

        $getCustomOptionMap = [['bundle_selection_ids', $option]];
        foreach ($selectionIds as $key => $selectionId) {
            $getCustomOptionMap[] = ['selection_qty_' . $selectionId, $sections[$key]];
        }
        $product = $this->prepareProductMock($parentPrice, $parentProductId, $customerGroupId, $storeId);
        $product->expects($this->atLeastOnce())
            ->method('getCustomOption')
            ->willReturnMap($getCustomOptionMap);

        $getRulePriceMap = [
            ["{$dateString}|{$websiteId}|{$customerGroupId}|{$parentProductId}", false]
        ];
        foreach ($productIds as $key => $productId) {
            $getRulePriceMap[] = ["{$dateString}|{$websiteId}|{$customerGroupId}|{$productId}", $rulePrices[$key]];
        }
        $this->rulePricesStorage->expects($this->any())
            ->method('getRulePrice')
            ->willReturnMap($getRulePriceMap);

        $this->assertEquals($isApplied, $this->bundleProduct->isApplied($product));
    }

    /**
     * @return array
     */
    public function isAppliedWithChildProductsDataProvider()
    {
        return [
            'applied' => [
                'isApplied' => true,
                'parentProductId' => 56,
                'selectionIds' => [2, 4, 7],
                'rulePrices' => ['0.0', false, '4.'],
                'prices' => [10, 20, 30],
                'productIds' => [51, 43, 65],
                'customerGroupId' => 3,
                'parentPrice' => 120,
                'storeId' => 2,
                'websiteId' => 1,
                'dateString' => 'format_date'
            ],
            'not applied' => [
                'isApplied' => false,
                'parentProductId' => 32,
                'selectionIds' => [12, 13, 14, 15],
                'rulePrices' => ['0.0', false, null, 0],
                'prices' => [100, 200, 300, 400],
                'productIds' => [11, 323, 434, 554],
                'customerGroupId' => 23,
                'parentPrice' => 12,
                'storeId' => 5,
                'websiteId' => 121,
                'dateString' => 'y-m-d'
            ]
        ];
    }

    public function testIsAppliedWithChildProductsIfOneHasSpecialPrice() {
        $dateString = 'y-m-d';
        $storeId = 321;
        $websiteId = 554;
        $parentPrice = 0;
        $customerGroupId = 12;

        $parentProductId = 200;
        $price1 = 100;
        $basePrice1 = 121;
        $selection1Id = 40;
        $product1Mock = $this->prepareProductMockWithSpecialPrice($price1, $basePrice1);

        $selection2Id = 402;

        $section1 = $this->createSectionMock();
        $section1->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($product1Mock);

        $section2 = $this->createSectionMock();
        $section2->expects($this->never())->method('getProduct');

        $this->prepareDateMock($dateString, $storeId);

        $this->prepareStoreMock($storeId, $websiteId);

        $option = $this->getMockBuilder(ProductConfigurationOption::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $option->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn(json_encode([$selection1Id, $selection2Id]));

        $product = $this->prepareProductMock($parentPrice, $parentProductId, $customerGroupId, $storeId);
        $product->expects($this->exactly(2))
            ->method('getCustomOption')
            ->willReturnMap([
                ['bundle_selection_ids', $option],
                ['selection_qty_' . $selection1Id, $section1]
            ]);

        $this->prepareRulePriceStorage(0, $dateString, $websiteId, $customerGroupId, $parentProductId);

        $this->assertTrue($this->bundleProduct->isApplied($product));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product\Configuration\Item\Option
     */
    private function createSectionMock()
    {
        return $this->getMockBuilder(ProductConfigurationOption::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();
    }
}
