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
use Swarming\SubscribePro\Model\Rule\Condition\Product as ProductCondition;

class ProductConditionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscriptionOptions()
    {
        $quoteItemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()->getMock();

        // Test normal subscription product && any subscription status
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Weekly'
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_part_of_subscription');
        $productConditionMock->method('getValueParsed')
            ->willReturn(ProductCondition::SUBSCRIPTION_STATUS_ANY);
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), true);
        // End normal subscription product && any subscription status

        // Test normal subscription product && new subscription status
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Weekly'
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_part_of_subscription');
        $productConditionMock->method('getValueParsed')
            ->willReturn(ProductCondition::SUBSCRIPTION_STATUS_NEW);
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), true);
        // End normal subscription product && new subscription status

        // Test normal subscription product && fulfilling subscription status
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Weekly'
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_part_of_subscription');
        $productConditionMock->method('getValueParsed')
            ->willReturn(ProductCondition::SUBSCRIPTION_STATUS_REORDER);
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), false);
        // End normal subscription product && fulfilling subscription status

        // Fulfilling subscription product && any sub status
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'is_fulfilling' => true,
                'interval' => 'Weekly',
                'reorder_ordinal' => 1,
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_part_of_subscription');
        $productConditionMock->method('getValueParsed')
            ->willReturn(ProductCondition::SUBSCRIPTION_STATUS_ANY);
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), true);
        // End fulfilling subscription product && any sub status

        // Fulfilling subscription product && new sub status
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'is_fulfilling' => true,
                'interval' => 'Weekly',
                'reorder_ordinal' => 1,
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_part_of_subscription');
        $productConditionMock->method('getValueParsed')
            ->willReturn(ProductCondition::SUBSCRIPTION_STATUS_NEW);
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), false);
        // End fulfilling subscription product && new sub status

        // Fulfilling subscription product && fulfilling sub status
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'is_fulfilling' => true,
                'interval' => 'Weekly',
                'reorder_ordinal' => 1,
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_part_of_subscription');
        $productConditionMock->method('getValueParsed')
            ->willReturn(ProductCondition::SUBSCRIPTION_STATUS_REORDER);
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), true);
        // End fulfilling subscription product && fulfilling

        // New subscription product && Interval check
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Weekly',
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_subscription_interval');
        $productConditionMock->method('getValueParsed')
            ->willReturn('Weekly');
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), true);
        // New subscription product && Interval check

        // New subscription product && Interval check
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Weekly',
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_subscription_interval');
        $productConditionMock->method('getValueParsed')
            ->willReturn('Monthly');
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), false);
        // End fulfilling subscription product && Interval check

        // New subscription product && reorder ordinal check
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Weekly',
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_subscription_reorder_ordinal');
        $productConditionMock->method('getValueParsed')
            ->willReturn('0');
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), true);
        // End new subscription product && reorder ordinal check

        // New subscription product && reorder ordinal check
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'option' => 'subscription',
                'interval' => 'Weekly',
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_subscription_reorder_ordinal');
        $productConditionMock->method('getValueParsed')
            ->willReturn('2');
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), false);
        // End new subscription product && reorder ordinal check

        // Fulfilling subscription product && reorder ordinal check
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'is_fulfilling' => 'subscription',
                'interval' => 'Weekly',
                'reorder_ordinal' => '2'
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_subscription_reorder_ordinal');
        $productConditionMock->method('getValueParsed')
            ->willReturn('2');
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), true);
        // Fulfilling subscription product && reorder ordinal check

        // Fulfilling subscription product && reorder ordinal check
        $quoteItemHelperMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()->getMock();
        $quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionParams')
            ->willReturn([
                'is_fulfilling' => 'subscription',
                'interval' => 'Weekly',
                'reorder_ordinal' => '2'
            ]);

        $productConditionMock = $this->getProductConditionMock($quoteItemHelperMock);
        $productConditionMock->method('getAttribute')
            ->willReturn('quote_item_subscription_reorder_ordinal');
        $productConditionMock->method('getValueParsed')
            ->willReturn('3');
        $productConditionMock->method('getOperatorForValidate')
            ->willReturn('==');

        $this->assertSame($productConditionMock->validate($quoteItemMock), false);
        // Fulfilling subscription product && reorder ordinal check
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

        return $this->getMockBuilder(ProductCondition::class)
            ->setConstructorArgs([
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
            ])
            ->setMethods([
                'getValueParsed',
                'getOperatorForValidate',
                'getAttribute',
            ])
            ->getMock();
    }
}
