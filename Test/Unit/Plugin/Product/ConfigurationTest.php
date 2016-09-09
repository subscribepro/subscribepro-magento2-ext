<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Swarming\SubscribePro\Plugin\Product\Configuration;
use Swarming\SubscribePro\Helper\Product as ProductHelper;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface as ConfigurationItemInterface;
use Magento\Catalog\Helper\Product\Configuration as ProductConfiguration;
use SubscribePro\Service\Product\ProductInterface as PlatformProductInterface;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\Product\Configuration
     */
    protected $configurationPlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Product
     */
    protected $productHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    protected function setUp()
    {
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->configurationPlugin = new Configuration(
            $this->productHelperMock,
            $this->quoteItemHelperMock
        );
    }

    public function testAroundGetCustomOptionsIfSubscribeProNotEnabled()
    {
        $subjectResult = ['result'];
        $subjectMock = $this->createProductConfigurationMock();
        $productMock = $this->createProductMock();

        $configurationItemMock = $this->createProductConfigurationItemMock();
        $configurationItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);

        $proceed = $this->createProceedCallback($subjectResult);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(false);

        $this->assertEquals(
            $subjectResult,
            $this->configurationPlugin->aroundGetCustomOptions($subjectMock, $proceed, $configurationItemMock)
        );
    }

    /**
     * @param array $subjectResult
     * @param string $subscriptionOption
     * @param string $subscriptionInterval
     * @param array $result
     * @dataProvider aroundGetCustomOptionsDataProvider
     */
    public function testAroundGetCustomOptions($subjectResult, $subscriptionOption, $subscriptionInterval, $result)
    {
        $subjectMock = $this->createProductConfigurationMock();
        $productMock = $this->createProductMock();

        $configurationItemMock = $this->createProductConfigurationItemMock();
        $configurationItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);

        $proceed = $this->createProceedCallback($subjectResult);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionOption')
            ->with($configurationItemMock)
            ->willReturn($subscriptionOption);
        $this->quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionInterval')
            ->with($configurationItemMock)
            ->willReturn($subscriptionInterval);

        $this->assertEquals(
            $result,
            $this->configurationPlugin->aroundGetCustomOptions($subjectMock, $proceed, $configurationItemMock)
        );
    }

    /**
     * @return array
     */
    public function aroundGetCustomOptionsDataProvider()
    {
        return [
            'Unknown subscription option:return subject result' => [
                'subjectResult' => ['key' => 'value'],
                'subscriptionOption' => 'unknown',
                'subscriptionInterval' => null,
                'result' => ['key' => 'value']
            ],
            'Onetime purchase subscription option:return merged result' => [
                'subjectResult' => [
                    ['label' => 'some_label', 'value' => 'some_value']
                ],
                'subscriptionOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'subscriptionInterval' => 'interval',
                'result' => [
                    ['label' => 'some_label', 'value' => 'some_value'],
                    ['label' => __('Delivery'), 'value' => __('One Time')],
                ]
            ],
            'Subscribe subscription option:return merged result' => [
                'subjectResult' => [
                    ['label' => 'key', 'value' => 'val']
                ],
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => 'monthly',
                'result' => [
                    ['label' => 'key', 'value' => 'val'],
                    ['label' => __('Regular Delivery'), 'value' => __('monthly')],
                ]
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\Data\ProductInterface
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface
     */
    private function createProductConfigurationItemMock()
    {
        return $this->getMockBuilder(ConfigurationItemInterface::class)->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Helper\Product\Configuration
     */
    private function createProductConfigurationMock()
    {
        return $this->getMockBuilder(ProductConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $result
     * @return callable
     */
    private function createProceedCallback(array $result)
    {
        return function ($item) use ($result) {
            return $result;
        };
    }
}
