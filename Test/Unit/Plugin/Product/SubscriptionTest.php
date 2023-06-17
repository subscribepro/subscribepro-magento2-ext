<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\View as ProductView;
use Swarming\SubscribePro\Helper\Product as ProductHelper;
use Swarming\SubscribePro\Model\Config\SubscriptionDiscount as SubscriptionDiscountConfig;
use Swarming\SubscribePro\Plugin\Product\Subscription;

class SubscriptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\Product\Subscription
     */
    protected $subscriptionPlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Product
     */
    protected $productHelperMock;

    protected function setUp(): void
    {
        $this->subscriptionDiscountConfigMock = $this->getMockBuilder(SubscriptionDiscountConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->subscriptionPlugin = new Subscription(
            $this->subscriptionDiscountConfigMock,
            $this->productHelperMock
        );
    }

    public function testAfterHasOptionsIfProductViewHasOptions()
    {
        $hasOptions = true;
        $subjectMock = $this->createProductViewMock();

        $this->subscriptionDiscountConfigMock->expects($this->never())->method('isEnabled');
        $this->productHelperMock->expects($this->never())->method('isSubscriptionEnabled');

        $this->assertTrue($this->subscriptionPlugin->afterHasOptions($subjectMock, $hasOptions));
    }

    /**
     * @param bool $isSubscriptionEnabled
     * @param bool $isProductSubscriptionEnabled
     * @param bool $result
     * @dataProvider afterHasOptionsDataProvider
     */
    public function testAfterHasOptions($isSubscriptionEnabled, $isProductSubscriptionEnabled, $result)
    {
        $hasOptions = false;
        $productMock = $this->createProductMock();

        $subjectMock = $this->createProductViewMock();
        $subjectMock->expects($this->any())->method('getProduct')->willReturn($productMock);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn($isSubscriptionEnabled);

        $this->productHelperMock->expects($this->any())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn($isProductSubscriptionEnabled);

        $this->assertEquals(
            $result,
            $this->subscriptionPlugin->afterHasOptions($subjectMock, $hasOptions)
        );
    }

    /**
     * @return array
     */
    public function afterHasOptionsDataProvider()
    {
        return [
            'Subscribe pro not enabled' => [
                'isSubscriptionEnabled' => false,
                'isProductSubscriptionEnabled' => true,
                'result' => false
            ],
            'Not subscription product' => [
                'isSubscriptionEnabled' => true,
                'isProductSubscriptionEnabled' => false,
                'result' => false
            ],
            'Subscription product' => [
                'isSubscriptionEnabled' => true,
                'isProductSubscriptionEnabled' => true,
                'result' => true
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
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Block\Product\View
     */
    private function createProductViewMock()
    {
        return $this->getMockBuilder(ProductView::class)->disableOriginalConstructor()->getMock();
    }
}
