<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote\SubscriptionOption;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\TestFramework\Unit\Matcher\MethodInvokedAtIndex;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater;

class UpdaterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater
     */
    protected $subscriptionOptionUpdater;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    protected function setUp(): void
    {
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->subscriptionOptionUpdater = new Updater($this->quoteItemHelperMock);
    }

    /**
     * @param null|string $subscriptionOption
     * @param string $subscriptionInterval
     * @param string $defaultSubscriptionOption
     * @param string $subscriptionOptionMode
     * @param array $productIntervals
     * @param array $warnings
     * @dataProvider updateIfOneTimePurchaseOptionDataProvider
     */
    public function testUpdateIfOneTimePurchaseOption(
        $subscriptionOption,
        $subscriptionInterval,
        $defaultSubscriptionOption,
        $subscriptionOptionMode,
        $productIntervals,
        $warnings
    ) {
        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getSubscriptionOptionMode')
            ->willReturn($subscriptionOptionMode);
        $platformProductMock->expects($this->once())
            ->method('getIntervals')
            ->willReturn($productIntervals);
        $platformProductMock->expects($this->any())
            ->method('getDefaultSubscriptionOption')
            ->willReturn($defaultSubscriptionOption);

        $quoteItemMock = $this->createQuoteItemMock();

        $this->quoteItemHelperMock->expects($this->at(0))
            ->method('setSubscriptionParam')
            ->with($quoteItemMock, SubscriptionOptionInterface::OPTION, PlatformProductInterface::SO_ONETIME_PURCHASE);
        $this->quoteItemHelperMock->expects($this->at(1))
            ->method('setSubscriptionParam')
            ->with($quoteItemMock, SubscriptionOptionInterface::INTERVAL, null);

        $this->assertEquals(
            $warnings,
            $this->subscriptionOptionUpdater->update(
                $quoteItemMock,
                $platformProductMock,
                $subscriptionOption,
                $subscriptionInterval
            )
        );
    }

    /**
     * @return array
     */
    public function updateIfOneTimePurchaseOptionDataProvider()
    {
        return [
            'Empty product intervals' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => 'monthly',
                'defaultSubscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => [],
                'warnings' => []
            ],
            'Subscription option is empty and default option is one_time_purchase' => [
                'subscriptionOption' => null,
                'subscriptionInterval' => 'daily',
                'defaultSubscriptionOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => ['daily'],
                'warnings' => []
            ],
            'Subscription option is one_time_purchase' => [
                'subscriptionOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'subscriptionInterval' => '3 days',
                'defaultSubscriptionOption' => null,
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => ['3 days'],
                'warnings' => []
            ],
        ];
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The product is not configured properly, please contact customer support.
     */
    public function testFailToUpdateIfSubscriptionOnlyAndEmptyIntervals()
    {
        $subscriptionInterval = 'daily';
        $productIntervals = [];
        $subscriptionOption = 'any_option';
        $quoteItemMock = $this->createQuoteItemMock();

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getSubscriptionOptionMode')
            ->willReturn(PlatformProductInterface::SOM_SUBSCRIPTION_ONLY);
        $platformProductMock->expects($this->once())
            ->method('getIntervals')
            ->willReturn($productIntervals);
        $platformProductMock->expects($this->never())->method('getDefaultSubscriptionOption');

        $this->quoteItemHelperMock->expects($this->never())->method('setSubscriptionParam');

        $this->subscriptionOptionUpdater->update(
            $quoteItemMock,
            $platformProductMock,
            $subscriptionOption,
            $subscriptionInterval
        );
    }

    /**
     * @param null|string $subscriptionOption
     * @param string $subscriptionInterval
     * @param string $defaultSubscriptionOption
     * @param string $subscriptionOptionMode
     * @param array $productIntervals
     * @param string $productName
     * @param int $qty
     * @param int|null $minQty
     * @param int|null $maxQty
     * @param int|null $originalQty
     * @param int $finalQty
     * @param array $warnings
     * @dataProvider updateIfSubscribeOptionAndInvalidQtyDataProvider
     */
    public function testUpdateIfSubscribeOptionAndInvalidQty(
        $subscriptionOption,
        $subscriptionInterval,
        $defaultSubscriptionOption,
        $subscriptionOptionMode,
        $productIntervals,
        $productName,
        $qty,
        $minQty,
        $maxQty,
        $originalQty,
        $finalQty,
        $warnings
    ) {
        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getSubscriptionOptionMode')
            ->willReturn($subscriptionOptionMode);
        $platformProductMock->expects($this->any())
            ->method('getIntervals')
            ->willReturn($productIntervals);
        $platformProductMock->expects($this->any())
            ->method('getDefaultSubscriptionOption')
            ->willReturn($defaultSubscriptionOption);
        $platformProductMock->expects($this->any())->method('getMinQty')->willReturn($minQty);
        $platformProductMock->expects($this->any())->method('getMaxQty')->willReturn($maxQty);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getName')->willReturn($productName);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->any())->method('getQty')->willReturn($qty);
        $quoteItemMock->expects($this->once())
            ->method('getOrigData')
            ->with(CartItemInterface::KEY_QTY)
            ->willReturn($originalQty);
        $quoteItemMock->expects($this->once())->method('setQty')->with($finalQty);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);

        $this->quoteItemHelperMock->expects($this->at(0))
            ->method('setSubscriptionParam')
            ->with($quoteItemMock, SubscriptionOptionInterface::OPTION, PlatformProductInterface::SO_SUBSCRIPTION);
        $this->quoteItemHelperMock->expects($this->at(1))
            ->method('setSubscriptionParam')
            ->with($quoteItemMock, SubscriptionOptionInterface::INTERVAL, $subscriptionInterval);

        $this->assertEquals(
            $warnings,
            $this->subscriptionOptionUpdater->update(
                $quoteItemMock,
                $platformProductMock,
                $subscriptionOption,
                $subscriptionInterval
            )
        );
    }

    /**
     * @return array
     */
    public function updateIfSubscribeOptionAndInvalidQtyDataProvider()
    {
        return [
            'Subscription only mode:qty less than min qty:revert qty' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => 'monthly',
                'defaultSubscriptionOption' => null,
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_ONLY,
                'productIntervals' => ['monthly'],
                'productName' => 'product_name',
                'qty' => 1,
                'minQty' => 2,
                'maxQty' => 5,
                'originalQty' => 3,
                'finalQty' => 3,
                'warnings' => [
                    __('Product "%1" requires minimum quantity of %2 for subscription.', 'product_name', 2)
                ]
            ],
            'Subscription only mode:qty less than min qty:no origin qty:set min qty' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => 'weekly',
                'defaultSubscriptionOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => ['weekly'],
                'productName' => 'laptop',
                'qty' => 3,
                'minQty' => 4,
                'maxQty' => null,
                'originalQty' => null,
                'finalQty' => 4,
                'warnings' => [
                    __('Product "%1" requires minimum quantity of %2 for subscription.', 'laptop', 4)
                ]
            ],
            'Subscribe option:qty greater than max qty:revert qty' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => 'daily',
                'defaultSubscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => ['daily'],
                'productName' => 'some_name',
                'qty' => 11,
                'minQty' => 1,
                'maxQty' => 7,
                'originalQty' => 5,
                'finalQty' => 5,
                'warnings' => [
                    __('Product "%1" allows maximum quantity of %2 for subscription.', 'some_name', 7)
                ]
            ],
            'Subscribe option:qty greater than max qty:no origin qty:set max qty' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => '2 days',
                'defaultSubscriptionOption' => PlatformProductInterface::SO_ONETIME_PURCHASE,
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => ['2 days', '3 days'],
                'productName' => 'chair',
                'qty' => 100,
                'minQty' => null,
                'maxQty' => 30,
                'originalQty' => null,
                'finalQty' => 30,
                'warnings' => [
                    __('Product "%1" allows maximum quantity of %2 for subscription.', 'chair', 30)
                ]
            ],
        ];
    }

    /**
     * @param null|string $subscriptionOption
     * @param string $subscriptionInterval
     * @param string $defaultInterval
     * @param string $intervalFromQuoteItem
     * @param string $subscriptionOptionMode
     * @param string $finalInterval
     * @param array $productIntervals
     * @param int $qty
     * @param int|null $minQty
     * @param int|null $maxQty
     * @param array $warnings
     * @dataProvider updateIfSubscribeOptionAndValidQtyDataProvider
     */
    public function testUpdateIfSubscribeOptionAndValidQty(
        $subscriptionOption,
        $subscriptionInterval,
        $defaultInterval,
        $intervalFromQuoteItem,
        $finalInterval,
        $subscriptionOptionMode,
        $productIntervals,
        $qty,
        $minQty,
        $maxQty,
        $warnings
    ) {
        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getSubscriptionOptionMode')
            ->willReturn($subscriptionOptionMode);
        $platformProductMock->expects($this->any())
            ->method('getIntervals')
            ->willReturn($productIntervals);
        $platformProductMock->expects($this->any())
            ->method('getDefaultInterval')
            ->willReturn($defaultInterval);
        $platformProductMock->expects($this->any())->method('getMinQty')->willReturn($minQty);
        $platformProductMock->expects($this->any())->method('getMaxQty')->willReturn($maxQty);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->any())->method('getQty')->willReturn($qty);
        $quoteItemMock->expects($this->never())->method('setQty');

        $this->quoteItemHelperMock->expects($this->any())
            ->method('getSubscriptionInterval')
            ->with($quoteItemMock)
            ->willReturn($intervalFromQuoteItem);
        $this->quoteItemHelperMock->expects(new MethodInvokedAtIndex(0))
            ->method('setSubscriptionParam')
            ->with($quoteItemMock, SubscriptionOptionInterface::OPTION, PlatformProductInterface::SO_SUBSCRIPTION);
        $this->quoteItemHelperMock->expects(new MethodInvokedAtIndex(1))
            ->method('setSubscriptionParam')
            ->with($quoteItemMock, SubscriptionOptionInterface::INTERVAL, $finalInterval);

        $this->assertEquals(
            $warnings,
            $this->subscriptionOptionUpdater->update(
                $quoteItemMock,
                $platformProductMock,
                $subscriptionOption,
                $subscriptionInterval
            )
        );
    }

    /**
     * @return array
     */
    public function updateIfSubscribeOptionAndValidQtyDataProvider()
    {
        return [
            'Subscription only mode:no default intervals: set first from product intervals' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => null,
                'defaultInterval' => null,
                'intervalFromQuoteItem' => null,
                'finalInterval' => 'monthly',
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_ONLY,
                'productIntervals' => ['monthly', 'daily'],
                'qty' => 3,
                'minQty' => 2,
                'maxQty' => 5,
                'warnings' => []
            ],
            'Subscription only mode:set default interval' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => null,
                'defaultInterval' => 'daily',
                'intervalFromQuoteItem' => null,
                'finalInterval' => 'daily',
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_ONLY,
                'productIntervals' => ['monthly', 'daily'],
                'qty' => 2,
                'minQty' => 1,
                'maxQty' => null,
                'warnings' => []
            ],
            'Subscribe option:set interval from quote item' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => null,
                'defaultInterval' => 'daily',
                'intervalFromQuoteItem' => 'weekly',
                'finalInterval' => 'weekly',
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => ['monthly', 'daily', 'weekly'],
                'qty' => 4,
                'minQty' => null,
                'maxQty' => 5,
                'warnings' => []
            ],
            'Subscribe option:subscription interval not valid: set interval from quote item' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => '1 day',
                'defaultInterval' => '3 days',
                'intervalFromQuoteItem' => '4 days',
                'finalInterval' => '4 days',
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => ['3 days', '4 days'],
                'qty' => 10,
                'minQty' => 5,
                'maxQty' => 15,
                'warnings' => [__('Subscription interval is not valid.')]
            ],
            'Subscribe option:subscription interval valid: set subscription interval' => [
                'subscriptionOption' => PlatformProductInterface::SO_SUBSCRIPTION,
                'subscriptionInterval' => '1 day',
                'defaultInterval' => '2 days',
                'intervalFromQuoteItem' => '3 days',
                'finalInterval' => '1 day',
                'subscriptionOptionMode' => PlatformProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                'productIntervals' => ['1 day', '2 days', '3 days'],
                'qty' => 10,
                'minQty' => 9,
                'maxQty' => 11,
                'warnings' => []
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createPlatformProductMock()
    {
        return $this->getMockBuilder(PlatformProductInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\Data\ProductInterface
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)->getMock();
    }
}
