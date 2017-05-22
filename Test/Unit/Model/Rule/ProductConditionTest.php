<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Rule;

use \Magento\Rule\Model\Condition\Context;
use \Magento\Backend\Helper\Data;
use \Magento\Eav\Model\Config;
use \Magento\Catalog\Model\ProductFactory;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Catalog\Model\ResourceModel\Product;
use \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use \Magento\Framework\Locale\FormatInterface;
use \Magento\Quote\Model\Quote\Item;
use \Swarming\SubscribePro\Helper\QuoteItem;

class ProductConditionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Rule\Model\Condition\Context
     */
    protected $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Backend\Helper\Data
     */
    protected $backendDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Eav\Model\Config
     */
    protected $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\ProductFactory
     */
    protected $productFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\ResourceModel\Product
     */
    protected $productResourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection
     */
    protected $attrSetCollectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormatMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    /**
     * @var \Swarming\SubscribePro\Model\Rule\Condition\Product
     */
    protected $productCondition;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()->getMock();
        $this->backendDataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()->getMock();
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()->getMock();
        $this->productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->productResourceMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()->getMock();
        $this->productResourceMock->method('loadAllAttributes')
            ->willReturn($this->productResourceMock);
        $this->productResourceMock->method('getAttributesByCode')
            ->willReturn([]);

        $this->attrSetCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()->getMock();
        $this->localeFormatMock = $this->getMockBuilder(FormatInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Monthly'
            ]);
        $data = [];

        $this->productCondition = new ProductCondition(
            $this->contextMock,
            $this->backendDataMock,
            $this->configMock,
            $this->productFactoryMock,
            $this->productRepositoryMock,
            $this->productResourceMock,
            $this->attrSetCollectionMock,
            $this->localeFormatMock,
            $this->quoteItemHelperMock,
            $data = []
        );
    }

    public function testGetSubscriptionOptions()
    {
        $mockModel = $this->getProductModelMock(
            true,
            false,
            null,
            'Monthly'
        );

        $this->assertSame($this->productCondition->exposedGetSubscriptionOptions($mockModel),
            [
                'new_subscription' => true,
                'is_fulfilling' => false,
                'reorder_ordinal' => 0,
                'interval' => 'Monthly',
            ]
        );
        $mockModel = $this->getProductModelMock(
            false,
            true,
            2,
            'Monthly'
        );

        $this->assertSame($this->productCondition->exposedGetSubscriptionOptions($mockModel),
            [
                'new_subscription' => false,
                'is_fulfilling' => true,
                'reorder_ordinal' => 2,
                'interval' => 'Monthly',
            ]
        );

        $mockModel = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()->getMock();
        $mockModel->method('getProductOption')
            ->willReturn(false);

        $this->assertSame($this->productCondition->exposedGetSubscriptionOptions($mockModel),
            [
                'new_subscription' => true,
                'is_fulfilling' => false,
                'reorder_ordinal' => 0,
                'interval' => 'Monthly',
            ]
        );
    }

    /**
     * @param bool $createsNewSubscription
     * @param bool $isFulfilling
     * @param string|null $reorderOrdinal
     * @param string|null $interval
     */
    protected function getProductModelMock($createsNewSubscription, $isFulfilling, $reorderOrdinal, $interval)
    {
        $mockSubscriptionOption = $this->getMockBuilder(SubscriptionOption::class)
            ->disableOriginalConstructor()->getMock();
        $mockSubscriptionOption->method('getCreatesNewSubscription')
            ->willReturn($createsNewSubscription);
        $mockSubscriptionOption->method('getIsFulfilling')
            ->willReturn($isFulfilling);
        $mockSubscriptionOption->method('getReorderOrdinal')
            ->willReturn($reorderOrdinal);
        $mockSubscriptionOption->method('getInterval')
            ->willReturn($interval);
        $mockExtensionAttributes = $this->getMockBuilder(ExtensionAttributes::class)
            ->disableOriginalConstructor()->getMock();
        $mockExtensionAttributes->method('getSubscriptionOption')
            ->willReturn($mockSubscriptionOption);
        $mockProductOption = $this->getMockBuilder(\Magento\Quote\Model\Quote\ProductOption::class)
            ->disableOriginalConstructor()->getMock();
        $mockProductOption->method('getExtensionAttributes')
            ->willReturn($mockExtensionAttributes);
        $mockModel = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()->getMock();
        $mockModel->method('getProductOption')
            ->willReturn($mockProductOption);
        return $mockModel;
    }
}
