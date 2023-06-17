<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\GroupedProduct;

use Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection as ProductCollection;
use Magento\GroupedProduct\Model\Product\Type\Grouped as TypeGroupedSubject;
use Swarming\SubscribePro\Plugin\GroupedProduct\TypeGrouped;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class TypeGroupedTest extends \PHPUnit\Framework\TestCase
{
    public function testAroundCompareOptionsIfSubjectResultIsFalse()
    {
        $subjectMock = $this->getMockBuilder(TypeGroupedSubject::class)
            ->disableOriginalConstructor()->getMock();

        $productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()->getMock();
        $productCollectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with(SubscriptionModifier::SUBSCRIPTION_ENABLED);

        $plugin = new TypeGrouped();

        $this->assertSame(
            $productCollectionMock,
            $plugin->afterGetAssociatedProductCollection($subjectMock, $productCollectionMock)
        );
    }
}
