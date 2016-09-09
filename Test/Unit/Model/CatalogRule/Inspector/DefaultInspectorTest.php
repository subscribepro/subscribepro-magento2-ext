<?php

namespace Swarming\SubscribePro\Test\Unit\Model\CatalogRule\Inspector;

use Swarming\SubscribePro\Model\CatalogRule\Inspector\DefaultInspector;

class DefaultInspectorTest extends AbstractInspector
{
    /**
     * @var \Swarming\SubscribePro\Model\CatalogRule\Inspector\DefaultInspector
     */
    protected $defaultInspector;

    protected function setUp()
    {
        parent::setUp();

        $this->defaultInspector = new DefaultInspector(
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

        if (!$customerGroupId) {
            $this->prepareCustomerSession($sessionCustomerGroupId);
        }

        $customerGroupId = $customerGroupId ?: $sessionCustomerGroupId;
        $this->prepareRulePriceStorage($rulePrice, $dateString, $websiteId, $customerGroupId, $productId);

        $this->assertEquals($isApplied, $this->defaultInspector->isApplied($product));
    }

    /**
     * @return array
     */
    public function isAppliedDataProvider()
    {
        return [
            'applied' => [
                'isApplied' => true,
                'rulePrice' => '5.1',
                'productId' => 12,
                'customerGroupId' => 2,
                'sessionCustomerGroupId' => 2,
                'storeId' => 3,
                'websiteId' => 4,
                'dateString' => 'date_format'
            ],
            'applied with customer group in session' => [
                'isApplied' => true,
                'rulePrice' => '5.1',
                'productId' => 9,
                'customerGroupId' => false,
                'sessionCustomerGroupId' => 5,
                'storeId' => 1,
                'websiteId' => 2,
                'dateString' => 'date_format_2'
            ],
            'not applied' => [
                'isApplied' => false,
                'rulePrice' => false,
                'productId' => 4,
                'customerGroupId' => 3,
                'sessionCustomerGroupId' => 3,
                'storeId' => 1,
                'websiteId' => 6,
                'dateString' => 'date_format_3'
            ],
            'not applied with 0. rule price' => [
                'isApplied' => false,
                'rulePrice' => '0.0',
                'productId' => 14,
                'customerGroupId' => 1,
                'sessionCustomerGroupId' => 1,
                'storeId' => 2,
                'websiteId' => 5,
                'dateString' => 'date_format_4'
            ],
        ];
    }
}
