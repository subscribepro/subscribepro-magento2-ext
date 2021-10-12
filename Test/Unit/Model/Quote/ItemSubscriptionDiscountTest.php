<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface as PlatformProductInterface;
use Swarming\SubscribePro\Model\Config\Source\CartRuleCombine;
use Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Swarming\SubscribePro\Platform\Manager\Product as ProductManager;
use Swarming\SubscribePro\Model\Config\SubscriptionDiscount as SubscriptionDiscountConfig;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

class ItemSubscriptionDiscountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Quote\ItemSubscriptionDiscount
     */
    protected $itemSubscriptionDiscount;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrencyMock;

    protected function setUp(): void
    {
        $this->subscriptionDiscountConfigMock = $this->getMockBuilder(SubscriptionDiscountConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformProductManagerMock = $this->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->priceCurrencyMock = $this->getMockBuilder(PriceCurrencyInterface::class)->getMock();

        $this->itemSubscriptionDiscount = new ItemSubscriptionDiscount(
            $this->subscriptionDiscountConfigMock,
            $this->platformProductManagerMock,
            $this->quoteItemHelperMock,
            $this->priceCurrencyMock
        );
    }

    /**
     * @param int $storeId
     * @param string $productSku
     * @param float $itemBasePrice
     * @param float $baseCartDiscount
     * @param bool $isDiscountPercentage
     * @param float $discount
     * @param float $baseSubscriptionDiscount
     * @param int $qty
     * @param string $cartRuleCombineType
     * @dataProvider processSubscriptionDiscountIfNotAppliedSubscriptionDiscountDataProvider
     */
    public function testProcessSubscriptionDiscountIfNotAppliedSubscriptionDiscount(
        $storeId,
        $productSku,
        $itemBasePrice,
        $baseCartDiscount,
        $isDiscountPercentage,
        $discount,
        $baseSubscriptionDiscount,
        $qty,
        $cartRuleCombineType
    ) {
        $rollbackCallback = $this->createCallbackMock();

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getIsDiscountPercentage')
            ->willReturn($isDiscountPercentage);
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($discount);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->once())->method('getBaseDiscountAmount')->willReturn($baseCartDiscount);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);
        $quoteItemMock->expects($this->never())->method('setDiscountAmount');
        $quoteItemMock->expects($this->never())->method('setBaseDiscountAmount');

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku)
            ->willReturn($platformProductMock);

        $this->subscriptionDiscountConfigMock->expects($this->exactly(2))
            ->method('getCartRuleCombineType')
            ->with($storeId)
            ->willReturn($cartRuleCombineType);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with($baseSubscriptionDiscount, $storeId);

        $this->itemSubscriptionDiscount->processSubscriptionDiscount($quoteItemMock, $itemBasePrice, $rollbackCallback);
    }

    /**
     * @return array
     */
    public function processSubscriptionDiscountIfNotAppliedSubscriptionDiscountDataProvider()
    {
        return [
            'Card combine type apply greatest:base subscription discount < base cart discount' => [
                'storeId' => 123123,
                'productSku' => 'sku_sku',
                'itemBasePrice' => 100,
                'baseCartDiscount' => 20,
                'isDiscountPercentage' => true,
                'discount' => 0.1,
                'baseSubscriptionDiscount' => 10,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_GREATEST,
            ],
            'Card combine type apply least:base subscription discount > base cart discount' => [
                'storeId' => 2421,
                'productSku' => 'sku',
                'itemBasePrice' => 10,
                'baseCartDiscount' => 0.5,
                'isDiscountPercentage' => false,
                'discount' => 2,
                'baseSubscriptionDiscount' => 2,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_LEAST,
            ],
            'Card combine type apply cart discount:cart rules applied' => [
                'storeId' => 3232,
                'productSku' => 'sku2',
                'itemBasePrice' => 15,
                'baseCartDiscount' => 2,
                'isDiscountPercentage' => false,
                'discount' => 5,
                'baseSubscriptionDiscount' => 10,
                'qty' => 2,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_CART_DISCOUNT,
            ],
        ];
    }

    /**
     * @param int $storeId
     * @param string $productSku
     * @param float $itemBasePrice
     * @param float $baseCartDiscount
     * @param bool $isDiscountPercentage
     * @param float $discount
     * @param float $baseSubscriptionDiscount
     * @param int $qty
     * @param string $cartRuleCombineType
     * @param float $subscriptionDiscount
     * @param array $discountDescriptions
     * @param array $updatedDiscountDescriptions
     * @dataProvider processSubscriptionDiscountIfOnlySubscriptionDiscountDataProvider
     */
    public function testProcessSubscriptionDiscountIfOnlySubscriptionDiscount(
        $storeId,
        $productSku,
        $itemBasePrice,
        $baseCartDiscount,
        $isDiscountPercentage,
        $discount,
        $baseSubscriptionDiscount,
        $qty,
        $cartRuleCombineType,
        $subscriptionDiscount,
        $discountDescriptions,
        $updatedDiscountDescriptions
    ) {
        $rollbackCallback = $this->createCallbackMock();
        $rollbackCallback->expects($this->once())->method('__invoke');

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getIsDiscountPercentage')
            ->willReturn($isDiscountPercentage);
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($discount);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->once())->method('getDiscountDescriptionArray')->willReturn($discountDescriptions);
        $addressMock->expects($this->once())->method('setDiscountDescriptionArray')->with($updatedDiscountDescriptions);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->once())->method('getBaseDiscountAmount')->willReturn($baseCartDiscount);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);
        $quoteItemMock->expects($this->any())->method('getAddress')->willReturn($addressMock);
        $quoteItemMock->expects($this->once())->method('setDiscountAmount')->with($subscriptionDiscount);
        $quoteItemMock->expects($this->once())->method('setBaseDiscountAmount')->with($baseSubscriptionDiscount);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku)
            ->willReturn($platformProductMock);

        $this->subscriptionDiscountConfigMock->expects($this->once())
            ->method('getCartRuleCombineType')
            ->with($storeId)
            ->willReturn($cartRuleCombineType);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with($baseSubscriptionDiscount, $storeId)
            ->willReturn($subscriptionDiscount);

        $this->itemSubscriptionDiscount->processSubscriptionDiscount($quoteItemMock, $itemBasePrice, $rollbackCallback);
    }

    /**
     * @return array
     */
    public function processSubscriptionDiscountIfOnlySubscriptionDiscountDataProvider()
    {
        return [
            'Card combine type apply greatest:base subscription discount > base cart discount' => [
                'storeId' => 123123,
                'productSku' => 'sku_sku',
                'itemBasePrice' => 100,
                'baseCartDiscount' => 5,
                'isDiscountPercentage' => true,
                'discount' => 0.1,
                'baseSubscriptionDiscount' => 10,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_GREATEST,
                'subscriptionDiscount' => 10,
                'discountDescriptions' => ['key' => 'value'],
                'updatedDiscountDescriptions' => [
                    'key' => 'value',
                    ItemSubscriptionDiscount::KEY_DISCOUNT_DESCRIPTION => __('Subscription')
                ],
            ],
            'Card combine type apply greatest:base subscription discount = base cart discount' => [
                'storeId' => 5654,
                'productSku' => 'product_sku',
                'itemBasePrice' => 100,
                'baseCartDiscount' => 10,
                'isDiscountPercentage' => true,
                'discount' => 0.05,
                'baseSubscriptionDiscount' => 10,
                'qty' => 2,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_GREATEST,
                'subscriptionDiscount' => 20,
                'discountDescriptions' => [],
                'updatedDiscountDescriptions' => [
                    ItemSubscriptionDiscount::KEY_DISCOUNT_DESCRIPTION => __('Subscription')
                ],
            ],
            'Card combine type apply least:base subscription discount = base cart discount' => [
                'storeId' => 6485,
                'productSku' => 'sku323',
                'itemBasePrice' => 10,
                'baseCartDiscount' => 2,
                'isDiscountPercentage' => false,
                'discount' => 2,
                'baseSubscriptionDiscount' => 2,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_LEAST,
                'subscriptionDiscount' => 3,
                'discountDescriptions' => ['some' => 'description'],
                'updatedDiscountDescriptions' => [
                    'some' => 'description',
                    ItemSubscriptionDiscount::KEY_DISCOUNT_DESCRIPTION => __('Subscription')
                ],
            ],
            'Card combine type apply least:base subscription discount < base cart discount' => [
                'storeId' => 123343,
                'productSku' => '22sku323',
                'itemBasePrice' => 100,
                'baseCartDiscount' => 15,
                'isDiscountPercentage' => true,
                'discount' => 0.05,
                'baseSubscriptionDiscount' => 10,
                'qty' => 2,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_LEAST,
                'subscriptionDiscount' => 10,
                'discountDescriptions' => ['another' => 'description'],
                'updatedDiscountDescriptions' => [
                    'another' => 'description',
                    ItemSubscriptionDiscount::KEY_DISCOUNT_DESCRIPTION => __('Subscription')
                ],
            ],
            'Card combine type apply cart discount:cart rules applied' => [
                'storeId' => 3232,
                'productSku' => 'sku2',
                'itemBasePrice' => 50,
                'baseCartDiscount' => 0,
                'isDiscountPercentage' => false,
                'discount' => 5,
                'baseSubscriptionDiscount' => 10,
                'qty' => 2,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_CART_DISCOUNT,
                'subscriptionDiscount' => 20,
                'discountDescriptions' => ['key' => 'value'],
                'updatedDiscountDescriptions' => [
                    'key' => 'value',
                    ItemSubscriptionDiscount::KEY_DISCOUNT_DESCRIPTION => __('Subscription')
                ],
            ],
            'Card combine type apply subscription' => [
                'storeId' => 43252,
                'productSku' => 'skusku',
                'itemBasePrice' => 80,
                'baseCartDiscount' => 12,
                'isDiscountPercentage' => false,
                'discount' => 10,
                'baseSubscriptionDiscount' => 10,
                'qty' => 1,
                'cartRuleCombineType' => CartRuleCombine::TYPE_APPLY_SUBSCRIPTION,
                'subscriptionDiscount' => 30,
                'discountDescriptions' => ['address' => 'description'],
                'updatedDiscountDescriptions' => [
                    'address' => 'description',
                    ItemSubscriptionDiscount::KEY_DISCOUNT_DESCRIPTION => __('Subscription')
                ],
            ],
        ];
    }

    public function testProcessSubscriptionDiscountIfCombineDiscounts()
    {
        $storeId = 54231;
        $productSku = 'productSku';
        $itemBasePrice = 100;
        $baseCartDiscount = 10;
        $isDiscountPercentage = true;
        $discount = 0.2;
        $baseSubscriptionDiscount = 40;
        $qty = 2;
        $subscriptionDiscount = 45;
        $discountDescriptions = ['discount' => 'description'];
        $updatedDiscountDescriptions = [
            'discount' => 'description',
            ItemSubscriptionDiscount::KEY_DISCOUNT_DESCRIPTION => __('Subscription')
        ];
        $oldDiscountAmount = 5;
        $oldBaseDiscountAmount = 2;
        $newDiscountAmount = 50;
        $newBaseDiscountAmount = 42;

        $rollbackCallback = $this->createCallbackMock();
        $rollbackCallback->expects($this->never())->method('__invoke');

        $quoteMock = $this->createQuoteMock();
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $platformProductMock = $this->createPlatformProductMock();
        $platformProductMock->expects($this->once())
            ->method('getIsDiscountPercentage')
            ->willReturn($isDiscountPercentage);
        $platformProductMock->expects($this->once())->method('getDiscount')->willReturn($discount);

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('getData')->with(ProductInterface::SKU)->willReturn($productSku);

        $addressMock = $this->createAddressMock();
        $addressMock->expects($this->once())->method('getDiscountDescriptionArray')->willReturn($discountDescriptions);
        $addressMock->expects($this->once())->method('setDiscountDescriptionArray')->with($updatedDiscountDescriptions);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getQuote')->willReturn($quoteMock);
        $quoteItemMock->expects($this->at(1))->method('getBaseDiscountAmount')->willReturn($baseCartDiscount);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getQty')->willReturn($qty);
        $quoteItemMock->expects($this->any())->method('getAddress')->willReturn($addressMock);
        $quoteItemMock->expects($this->once())->method('getDiscountAmount')->willReturn($oldDiscountAmount);
        $quoteItemMock->expects($this->at(6))->method('getBaseDiscountAmount')->willReturn($oldBaseDiscountAmount);
        $quoteItemMock->expects($this->once())->method('setDiscountAmount')->with($newDiscountAmount);
        $quoteItemMock->expects($this->once())->method('setBaseDiscountAmount')->with($newBaseDiscountAmount);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productSku)
            ->willReturn($platformProductMock);

        $this->subscriptionDiscountConfigMock->expects($this->exactly(2))
            ->method('getCartRuleCombineType')
            ->with($storeId)
            ->willReturn(CartRuleCombine::TYPE_COMBINE_SUBSCRIPTION);

        $this->priceCurrencyMock->expects($this->once())
            ->method('convertAndRound')
            ->with($baseSubscriptionDiscount, $storeId)
            ->willReturn($subscriptionDiscount);

        $this->itemSubscriptionDiscount->processSubscriptionDiscount($quoteItemMock, $itemBasePrice, $rollbackCallback);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getDiscountAmount',
                'getBaseDiscountAmount',
                'getQuote',
                'getProduct',
                'getAddress',
                'getQty',
                'setDiscountAmount',
                'setBaseDiscountAmount',
                '__wakeup'
            ])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartInterface
     */
    private function createQuoteMock()
    {
        return $this->getMockBuilder(CartInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Address
     */
    private function createAddressMock()
    {
        return $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(['setDiscountDescriptionArray', 'getDiscountDescriptionArray', '__wakeup'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|callable
     */
    private function createCallbackMock()
    {
        return $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createPlatformProductMock()
    {
        return $this->getMockBuilder(PlatformProductInterface::class)->getMock();
    }
}
