<?php

namespace Swarming\SubscribePro\Test\Unit\Model\CatalogRule\Inspector;

use Swarming\SubscribePro\Model\CatalogRule\Inspector\BundleProduct;
use Magento\Catalog\Model\Product\Configuration\Item\Option as ProductConfigurationOption;
use Magento\Catalog\Model\Product;

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

    /**
     * @param bool $isApplied
     * @param int $rulePrice
     * @param string $productId
     * @param bool $customerGroupId
     * @param string $sessionCustomerGroupId
     * @param string $storeId
     * @param string $websiteId
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
        $dateString
    ) {
        $this->prepareDateMock($dateString, $storeId);

        $this->prepareStoreMock($storeId, $websiteId);

        $product = $this->prepareProductMock($productId, $customerGroupId, $storeId);
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
                'dateString' => 'date_format_4'
            ],
        ];
    }

    /**
     * @param bool $isApplied
     * @param int $parentProductId
     * @param [] $selectionIds
     * @param [] $rulePrices
     * @param [] $productIds
     * @param bool $customerGroupId
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
        $productIds,
        $customerGroupId,
        $storeId,
        $websiteId,
        $dateString
    ) {
        $this->prepareDateMock($dateString, $storeId);

        $this->prepareStoreMock($storeId, $websiteId);

        $childProducts = [];
        foreach ($productIds as $productId) {
            $childProducts[] = $this->prepareProductMock($productId, $customerGroupId, $storeId);
        }

        $option = $this->getMockBuilder(ProductConfigurationOption::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
        $option->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn(serialize($selectionIds));

        $sections = [];
        foreach (array_keys($selectionIds) as $selectionKey) {
            $section = $this->getMockBuilder(ProductConfigurationOption::class)
                ->disableOriginalConstructor()
                ->setMethods(['getProduct'])
                ->getMock();
            $section->expects($this->atLeastOnce())
                ->method('getProduct')
                ->willReturn($childProducts[$selectionKey]);

            $sections[] = $section;
        }

        $getCustomOptionMap = [['bundle_selection_ids', $option]];
        foreach ($selectionIds as $key => $selectionId) {
            $getCustomOptionMap[] = ['selection_qty_' . $selectionId, $sections[$key]];
        }
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getStoreId', 'hasCustomerGroupId', 'getCustomerGroupId', 'getCustomOption', '__wakeup'])
            ->getMock();
        $product->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($parentProductId);
        $product->expects($this->atLeastOnce())
            ->method('hasCustomerGroupId')
            ->willReturn((bool)$customerGroupId);
        $product->expects($this->atLeastOnce())
            ->method('getCustomerGroupId')
            ->willReturn($customerGroupId);

        $product->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn($storeId);
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
                'productIds' => [51, 43, 65],
                'customerGroupId' => 3,
                'storeId' => 2,
                'websiteId' => 1,
                'dateString' => 'format_date'
            ],
            'not applied' => [
                'isApplied' => false,
                'parentProductId' => 56,
                'selectionIds' => [2, 4, 7],
                'rulePrices' => ['0.0', false, null],
                'productIds' => [51, 43, 65],
                'customerGroupId' => 3,
                'storeId' => 2,
                'websiteId' => 1,
                'dateString' => 'format_date'
            ]
        ];
    }
}
