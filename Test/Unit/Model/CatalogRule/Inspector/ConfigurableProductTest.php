<?php

namespace Swarming\SubscribePro\Test\Unit\Model\CatalogRule\Inspector;

use Swarming\SubscribePro\Model\CatalogRule\Inspector\ConfigurableProduct;
use Magento\Catalog\Model\Product\Configuration\Item\Option as ProductConfigurationOption;

class ConfigurableProductTest extends AbstractInspector
{
    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\Inspector\ConfigurableProduct
     */
    protected $configurableProduct;

    protected function setUp()
    {
        parent::setUp();

        $this->configurableProduct = new ConfigurableProduct(
            $this->customerSession,
            $this->storeManager,
            $this->localeDate,
            $this->rulePricesStorage
        );
    }

    public function testIsAppliedIfChildHasSpecialPrice() {
        $price = 100;
        $basePrice = 110;

        $childProduct = $this->prepareProductMockWithSpecialPrice($price, $basePrice);

        $option = $this->getMockBuilder(ProductConfigurationOption::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();
        $option->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($childProduct);

        $product = $this->createProductMock();
        $product->expects($this->atLeastOnce())
            ->method('getCustomOption')
            ->with('simple_product')
            ->willReturn($option);

        $this->rulePricesStorage->expects($this->never())->method('getRulePrice');

        $this->assertTrue($this->configurableProduct->isApplied($product));
    }

    public function testIsAppliedIfHasSpecialPrice() {
        $price = 100;
        $basePrice = 110;

        $product = $this->prepareProductMockWithSpecialPrice($price, $basePrice);

        $this->rulePricesStorage->expects($this->never())->method('getRulePrice');

        $this->assertTrue($this->configurableProduct->isApplied($product));
    }

    /**
     * @param bool $isApplied
     * @param int $rulePrice
     * @param float $price
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
        $price,
        $productId,
        $customerGroupId,
        $sessionCustomerGroupId,
        $storeId,
        $websiteId,
        $dateString
    ) {
        $this->prepareDateMock($dateString, $storeId);

        $this->prepareStoreMock($storeId, $websiteId);

        $product = $this->prepareChildProductMock($price, $productId, $customerGroupId, $storeId);

        if (!$customerGroupId) {
            $this->prepareCustomerSession($sessionCustomerGroupId);
        }

        $customerGroupId = $customerGroupId ?: $sessionCustomerGroupId;
        $this->prepareRulePriceStorage($rulePrice, $dateString, $websiteId, $customerGroupId, $productId);

        $this->assertEquals($isApplied, $this->configurableProduct->isApplied($product));
    }

    /**
     * @param float $price
     * @param int $productId
     * @param int $customerGroupId
     * @param int $storeId
     * @return \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareChildProductMock($price, $productId, $customerGroupId, $storeId)
    {
        $childProduct = $this->prepareProductMock($price, $productId, $customerGroupId, $storeId);

        $option = $this->getMockBuilder(ProductConfigurationOption::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();
        $option->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($childProduct);

        $product = $this->createProductMock();
        $product->expects($this->atLeastOnce())
            ->method('getCustomOption')
            ->with('simple_product')
            ->willReturn($option);

        return $product;
    }

    /**
     * @param bool $isApplied
     * @param int $rulePrice
     * @param float $price
     * @param string $productId
     * @param bool $customerGroupId
     * @param string $sessionCustomerGroupId
     * @param string $storeId
     * @param string $websiteId
     * @param string $dateString
     * @dataProvider isAppliedDataProvider
     */
    public function testIsAppliedIfNoChildProduct(
        $isApplied,
        $rulePrice,
        $price,
        $productId,
        $customerGroupId,
        $sessionCustomerGroupId,
        $storeId,
        $websiteId,
        $dateString
    ) {
        $this->prepareDateMock($dateString, $storeId);

        $this->prepareStoreMock($storeId, $websiteId);

        $product = $this->prepareProductMock($price, $productId, $customerGroupId, $storeId);
        $product->expects($this->atLeastOnce())
            ->method('getCustomOption')
            ->with('simple_product')
            ->willReturn(false);

        if (!$customerGroupId) {
            $this->prepareCustomerSession($sessionCustomerGroupId);
        }

        $customerGroupId = $customerGroupId ?: $sessionCustomerGroupId;
        $this->prepareRulePriceStorage($rulePrice, $dateString, $websiteId, $customerGroupId, $productId);

        $this->assertEquals($isApplied, $this->configurableProduct->isApplied($product));
    }

    /**
     * @return array
     */
    public function isAppliedDataProvider()
    {
        return [
            'applied' => [
                'isApplied' => true,
                'rulePrice' => '3.1',
                'price' => '50',
                'productId' => 76,
                'customerGroupId' => 1,
                'sessionCustomerGroupId' => 1,
                'storeId' => 1,
                'websiteId' => 2,
                'dateString' => 'date_format'
            ],
            'not applied' => [
                'isApplied' => false,
                'rulePrice' => false,
                'price' => '60',
                'productId' => 43,
                'customerGroupId' => 3,
                'sessionCustomerGroupId' => 5,
                'storeId' => 3,
                'websiteId' => 1,
                'dateString' => 'date_format_3'
            ],
            'not applied with 0. rule price' => [
                'isApplied' => false,
                'rulePrice' => '0.0',
                'price' => '70',
                'productId' => 7,
                'customerGroupId' => 2,
                'sessionCustomerGroupId' => 2,
                'storeId' => 3,
                'websiteId' => 1,
                'dateString' => 'date_format_4'
            ],
        ];
    }
}
