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
    public function testGetSubscriptionOptions()
    {
        $quoteItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()->getMock();

        // Test the base case of no subscription parameters
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);

        $this->assertSame($productConditionMock->exposedGetSubscriptionOptions($quoteItemMock),
            [
                'new_subscription' => false,
                'is_fulfilling' => false,
                'reorder_ordinal' => false,
                'interval' => false,
            ]
        );

        // Test the base case of onetime_purchase option
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'onetime_purchase'
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);

        $this->assertSame($productConditionMock->exposedGetSubscriptionOptions($quoteItemMock),
            [
                'new_subscription' => false,
                'is_fulfilling' => false,
                'reorder_ordinal' => false,
                'interval' => false,
            ]
        );

        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'is_fulfilling' => 1,
                'reorder_ordinal' => '2',
                'interval' => 'Monthly'
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);

        $this->assertSame($productConditionMock->exposedGetSubscriptionOptions($quoteItemMock),
            [
                'new_subscription' => false,
                'is_fulfilling' => true,
                'reorder_ordinal' => '2',
                'interval' => 'Monthly',
            ]
        );


        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Weekly'
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);

        $this->assertSame($productConditionMock->exposedGetSubscriptionOptions($quoteItemMock),
            [
                'new_subscription' => true,
                'is_fulfilling' => false,
                'reorder_ordinal' => 0,
                'interval' => 'Weekly',
            ]
        );
    }

    protected function getProductConditionMock($quoteItemHelperMock)
    {
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()->getMock();
        $backendDataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()->getMock();
        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()->getMock();
        $productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()->getMock();
        $productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()->getMock();

        $productResourceMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()->getMock();
        $productResourceMock->method('loadAllAttributes')
            ->willReturn($productResourceMock);
        $productResourceMock->method('getAttributesByCode')
            ->willReturn([]);

        $attrSetCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()->getMock();
        $localeFormatMock = $this->getMockBuilder(FormatInterface::class)
            ->disableOriginalConstructor()->getMock();

        return new ProductCondition(
            $contextMock,
            $backendDataMock,
            $configMock,
            $productFactoryMock,
            $productRepositoryMock,
            $productResourceMock,
            $attrSetCollectionMock,
            $localeFormatMock,
            $quoteItemHelperMock,
            []
        );
    }
}
