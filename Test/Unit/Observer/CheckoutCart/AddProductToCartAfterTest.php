<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\CheckoutCart;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Swarming\SubscribePro\Api\Data\ProductInterface;
use Magento\Framework\App\State as AppState;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Model\Config\General as GeneralConfig;
use Magento\Framework\App\State;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater as SubscriptionOptionUpdater;
use Swarming\SubscribePro\Observer\CheckoutCart\AddProductToCartAfter;
use Swarming\SubscribePro\Platform\Manager\Product as ProductManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Swarming\SubscribePro\Helper\Product as ProductHelper;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;

class AddProductToCartAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\CheckoutCart\AddProductToCartAfter
     */
    protected $addProductToCartAfter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater
     */
    protected $subscriptionOptionUpdaterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\Product
     */
    protected $productHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\State
     */
    protected $appStateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Message\ManagerInterface
     */
    protected $messageManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\QuoteItem
     */
    protected $quoteItemHelperMock;

    protected function setUp(): void
    {
        $this->generalConfigMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->subscriptionOptionUpdaterMock = $this->getMockBuilder(SubscriptionOptionUpdater::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformProductManagerMock = $this->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->getMock();
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->appStateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->addProductToCartAfter = new AddProductToCartAfter(
            $this->generalConfigMock,
            $this->platformProductManagerMock,
            $this->subscriptionOptionUpdaterMock,
            $this->productRepositoryMock,
            $this->productHelperMock,
            $this->messageManagerMock,
            $this->appStateMock,
            $this->loggerMock,
            $this->quoteItemHelperMock
        );
    }

    public function testExecuteIfSubscribeProDisabled()
    {
        $observerMock = $this->createObserverMock();

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->quoteItemHelperMock->expects($this->never())->method('getSubscriptionParams');
        $this->platformProductManagerMock->expects($this->never())->method('getProduct');
        $this->subscriptionOptionUpdaterMock->expects($this->never())->method('update');

        $this->addProductToCartAfter->execute($observerMock);
    }

    public function testExecuteIfOneIsNotSubscriptionProduct()
    {
        $product2Sku = 'product2-sku';
        $subscriptionOption = 'subscribe';
        $subscriptionInterval = 'monthly';
        $platformProduct2Mock = $this->createPlatformProductMock();

        $subscription1Params = ['params'];
        $subscription2Params = [
            SubscriptionOptionInterface::OPTION => $subscriptionOption,
            SubscriptionOptionInterface::INTERVAL => $subscriptionInterval,
        ];

        $product1Mock = $this->createProductMock();
        $product2Mock = $this->createProductMock();
        $product2Mock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product2Sku);

        $quoteItem1Mock = $this->createQuoteItemMock();
        $quoteItem1Mock->expects($this->once())->method('getProduct')->willReturn($product1Mock);
        $quoteItem1Mock->expects($this->once())->method('getParentItem')->willReturn(null);

        $quoteItem2Mock = $this->createQuoteItemMock();
        $quoteItem2Mock->expects($this->once())->method('getProduct')->willReturn($product2Mock);
        $quoteItem2Mock->expects($this->once())->method('getParentItem')->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('items')
            ->willReturn([$quoteItem1Mock, $quoteItem2Mock]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->exactly(2))
            ->method('getSubscriptionParams')
            ->willReturnMap([
                [$quoteItem1Mock, $subscription1Params],
                [$quoteItem2Mock, $subscription2Params]
            ]);

        $this->productHelperMock->expects($this->exactly(2))
            ->method('isSubscriptionEnabled')
            ->willReturnMap([
                [$product1Mock, false],
                [$product2Mock, true]
            ]);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($product2Sku)
            ->willReturn($platformProduct2Mock);

        $this->subscriptionOptionUpdaterMock->expects($this->once())
            ->method('update')
            ->with($quoteItem2Mock, $platformProduct2Mock, $subscriptionOption, $subscriptionInterval)
            ->willReturn([]);

        $this->addProductToCartAfter->execute($observerMock);
    }

    public function testExecuteIfOneHasParent()
    {
        $product1ParentSku = 'product1-parent-sku';
        $product2Sku = 'product2-sku';
        $subscriptionOption = 'subscribe';
        $subscriptionInterval = 'monthly';
        $platformProduct1ParentMock = $this->createPlatformProductMock();
        $platformProduct2Mock = $this->createPlatformProductMock();

        $subscription1Params = [
            SubscriptionOptionInterface::OPTION => $subscriptionOption,
            SubscriptionOptionInterface::INTERVAL => $subscriptionInterval,
        ];
        $subscription2Params = [
            SubscriptionOptionInterface::OPTION => $subscriptionOption,
            SubscriptionOptionInterface::INTERVAL => $subscriptionInterval,
        ];

        $product1Mock = $this->createProductMock();

        $product1ParentMock = $this->createProductMock();
        $product1ParentMock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product1ParentSku);

        $product2Mock = $this->createProductMock();
        $product2Mock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product2Sku);

        $quoteItem1ParentMock = $this->createQuoteItemMock();
        $quoteItem1ParentMock->expects($this->atLeastOnce())->method('getProduct')->willReturn($product1ParentMock);

        $quoteItem1Mock = $this->createQuoteItemMock();
        $quoteItem1Mock->expects($this->once())->method('getProduct')->willReturn($product1Mock);
        $quoteItem1Mock->expects($this->atLeastOnce())->method('getParentItem')->willReturn($quoteItem1ParentMock);

        $quoteItem2Mock = $this->createQuoteItemMock();
        $quoteItem2Mock->expects($this->once())->method('getProduct')->willReturn($product2Mock);
        $quoteItem2Mock->expects($this->once())->method('getParentItem')->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('items')
            ->willReturn([$quoteItem1Mock, $quoteItem2Mock]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->exactly(2))
            ->method('getSubscriptionParams')
            ->willReturnMap([
                [$quoteItem1Mock, $subscription1Params],
                [$quoteItem2Mock, $subscription2Params]
            ]);

        $this->productHelperMock->expects($this->exactly(2))
            ->method('isSubscriptionEnabled')
            ->willReturnMap([
                [$product1ParentMock, true],
                [$product2Mock, true]
            ]);

        $this->platformProductManagerMock->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturnMap([
                [$product1ParentSku, null, $platformProduct1ParentMock],
                [$product2Sku, null, $platformProduct2Mock]
            ]);

        $this->subscriptionOptionUpdaterMock->expects($this->exactly(2))
            ->method('update')
            ->willReturnMap([
                [$quoteItem1Mock, $platformProduct1ParentMock, $subscriptionOption, $subscriptionInterval, []],
                [$quoteItem2Mock, $platformProduct2Mock, $subscriptionOption, $subscriptionInterval, []]
            ]);

        $this->addProductToCartAfter->execute($observerMock);
    }

    public function testExecuteIfNoFirstPlatformProductInDeveloperMode()
    {
        $exception = new NoSuchEntityException(__('platform error'));
        $product1Sku = 'product1-sku';
        $product2Sku = 'product2-sku';
        $subscriptionOption = 'subscribe';
        $subscriptionInterval = 'monthly';
        $platformProduct2Mock = $this->createPlatformProductMock();

        $subscription1Params = ['params'];
        $subscription2Params = [
            SubscriptionOptionInterface::OPTION => $subscriptionOption,
            SubscriptionOptionInterface::INTERVAL => $subscriptionInterval,
        ];

        $product1Mock = $this->createProductMock();
        $product1Mock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product1Sku);

        $product2Mock = $this->createProductMock();
        $product2Mock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product2Sku);

        $quoteItem1Mock = $this->createQuoteItemMock();
        $quoteItem1Mock->expects($this->once())->method('getProduct')->willReturn($product1Mock);
        $quoteItem1Mock->expects($this->once())->method('getParentItem')->willReturn(null);
        $quoteItem1Mock->expects($this->once())->method('isDeleted')->with(true);

        $quoteItem2Mock = $this->createQuoteItemMock();
        $quoteItem2Mock->expects($this->once())->method('getProduct')->willReturn($product2Mock);
        $quoteItem2Mock->expects($this->once())->method('getParentItem')->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('items')
            ->willReturn([$quoteItem1Mock, $quoteItem2Mock]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->exactly(2))
            ->method('getSubscriptionParams')
            ->willReturnMap([
                [$quoteItem1Mock, $subscription1Params],
                [$quoteItem2Mock, $subscription2Params]
            ]);

        $this->productHelperMock->expects($this->exactly(2))
            ->method('isSubscriptionEnabled')
            ->willReturnMap([[$product1Mock, true], [$product2Mock, true]]);

        $this->platformProductManagerMock->expects($this->at(0))
            ->method('getProduct')
            ->with($product1Sku)
            ->willThrowException($exception);

        $this->platformProductManagerMock->expects($this->at(1))
            ->method('getProduct')
            ->with($product2Sku)
            ->willReturn($platformProduct2Mock);

        $this->appStateMock->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_DEVELOPER);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with($exception->getMessage());

        $this->subscriptionOptionUpdaterMock->expects($this->once())
            ->method('update')
            ->with($quoteItem2Mock, $platformProduct2Mock, $subscriptionOption, $subscriptionInterval)
            ->willReturn([]);

        $this->addProductToCartAfter->execute($observerMock);
    }

    /**
     * @param string $product1Sku
     * @param string $product2Sku
     * @param \Exception $exception
     * @param array $subscription1Params
     * @param array $subscription2Params
     * @param string $subscriptionOption
     * @param string $subscriptionInterval
     * @param string $appMode
     * @dataProvider failToExecuteIfNoFirstPlatformProductDataProvider
     */
    public function testFailToExecuteIfNoFirstPlatformProduct(
        $product1Sku,
        $product2Sku,
        $exception,
        $subscription1Params,
        $subscription2Params,
        $subscriptionOption,
        $subscriptionInterval,
        $appMode
    ) {
        $platformProduct2Mock = $this->createPlatformProductMock();

        $product1Mock = $this->createProductMock();
        $product1Mock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product1Sku);
        $product2Mock = $this->createProductMock();
        $product2Mock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product2Sku);

        $quoteItem1Mock = $this->createQuoteItemMock();
        $quoteItem1Mock->expects($this->once())->method('getProduct')->willReturn($product1Mock);
        $quoteItem1Mock->expects($this->once())->method('getParentItem')->willReturn(null);

        $quoteItem2Mock = $this->createQuoteItemMock();
        $quoteItem2Mock->expects($this->once())->method('getProduct')->willReturn($product2Mock);
        $quoteItem2Mock->expects($this->once())->method('getParentItem')->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('items')
            ->willReturn([$quoteItem1Mock, $quoteItem2Mock]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->exactly(2))
            ->method('getSubscriptionParams')
            ->willReturnMap([
                [$quoteItem1Mock, $subscription1Params],
                [$quoteItem2Mock, $subscription2Params]
            ]);

        $this->productHelperMock->expects($this->exactly(2))
            ->method('isSubscriptionEnabled')
            ->willReturnMap([[$product1Mock, true], [$product2Mock, true]]);

        $this->platformProductManagerMock->expects($this->at(0))
            ->method('getProduct')
            ->with($product1Sku)
            ->willThrowException($exception);

        $this->platformProductManagerMock->expects($this->at(1))
            ->method('getProduct')
            ->with($product2Sku)
            ->willReturn($platformProduct2Mock);

        $this->appStateMock->expects($this->once())
            ->method('getMode')
            ->willReturn($appMode);

        $this->subscriptionOptionUpdaterMock->expects($this->once())
            ->method('update')
            ->with($quoteItem2Mock, $platformProduct2Mock, $subscriptionOption, $subscriptionInterval)
            ->willReturn([]);

        $this->addProductToCartAfter->execute($observerMock);
    }

    /**
     * @return array
     */
    public function failToExecuteIfNoFirstPlatformProductDataProvider()
    {
        return [
            'Default mode' => [
                'product1Sku' => 'product-1-sku',
                'product2Sku' => 'product-2-sku',
                'exception' => new NoSuchEntityException(__('log message')),
                'subscription1Params' => ['params'],
                'subscription2Params' => [
                    SubscriptionOptionInterface::OPTION => 'subscribe',
                    SubscriptionOptionInterface::INTERVAL => 'weekly',
                ],
                'subscriptionOption' => 'subscribe',
                'subscriptionInterval' => 'weekly',
                'appMode' => AppState::MODE_DEFAULT
            ],
            'Production mode' => [
                'product1Sku' => 'product1sku',
                'product2Sku' => 'product2sku',
                'exception' => new NoSuchEntityException(__('error message')),
                'subscription1Params' => ['params'],
                'subscription2Params' => [
                    SubscriptionOptionInterface::OPTION => 'onetime_purchase',
                    SubscriptionOptionInterface::INTERVAL => 'monthly',
                ],
                'subscriptionOption' => 'onetime_purchase',
                'subscriptionInterval' => 'monthly',
                'appMode' => AppState::MODE_PRODUCTION
            ]
        ];
    }

    /**
     * @param string $product1Sku
     * @param string $product2Sku
     * @param array $subscription1Params
     * @param string $subscription1Option
     * @param string $subscription1Interval
     * @param array $subscription2Params
     * @param string $subscription2Option
     * @param string $subscription2Interval
     * @param array $item1Warnings
     * @param array $item2Warnings
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $product1Sku,
        $product2Sku,
        $subscription1Params,
        $subscription1Option,
        $subscription1Interval,
        $subscription2Params,
        $subscription2Option,
        $subscription2Interval,
        $item1Warnings,
        $item2Warnings
    ) {
        $platformProduct1Mock = $this->createPlatformProductMock();
        $platformProduct2Mock = $this->createPlatformProductMock();

        $product1Mock = $this->createProductMock();
        $product1Mock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product1Sku);
        $product2Mock = $this->createProductMock();
        $product2Mock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($product2Sku);

        $quoteItem1Mock = $this->createQuoteItemMock();
        $quoteItem1Mock->expects($this->once())->method('getProduct')->willReturn($product1Mock);
        $quoteItem1Mock->expects($this->once())->method('getParentItem')->willReturn(null);

        $quoteItem2Mock = $this->createQuoteItemMock();
        $quoteItem2Mock->expects($this->once())->method('getProduct')->willReturn($product2Mock);
        $quoteItem2Mock->expects($this->once())->method('getParentItem')->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('items')
            ->willReturn([$quoteItem1Mock, $quoteItem2Mock]);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->exactly(2))
            ->method('getSubscriptionParams')
            ->willReturnMap([
                [$quoteItem1Mock, $subscription1Params],
                [$quoteItem2Mock, $subscription2Params]
            ]);

        $this->productHelperMock->expects($this->exactly(2))
            ->method('isSubscriptionEnabled')
            ->willReturnMap([[$product1Mock, true], [$product2Mock, true]]);

        $this->platformProductManagerMock->expects($this->at(0))
            ->method('getProduct')
            ->with($product1Sku)
            ->willReturn($platformProduct1Mock);

        $this->platformProductManagerMock->expects($this->at(1))
            ->method('getProduct')
            ->with($product2Sku)
            ->willReturn($platformProduct2Mock);

        $this->subscriptionOptionUpdaterMock->expects($this->at(0))
            ->method('update')
            ->with($quoteItem1Mock, $platformProduct1Mock, $subscription1Option, $subscription1Interval)
            ->willReturn($item1Warnings);

        $this->subscriptionOptionUpdaterMock->expects($this->at(1))
            ->method('update')
            ->with($quoteItem2Mock, $platformProduct2Mock, $subscription2Option, $subscription2Interval)
            ->willReturn($item2Warnings);

        $warningsMap = array_map(function ($warning) {
            return [$warning];
        }, array_merge($item1Warnings, $item2Warnings));
        $this->messageManagerMock->expects($this->exactly(count($item1Warnings) + count($item2Warnings)))
            ->method('addWarningMessage')
            ->willReturnMap($warningsMap);

        $this->addProductToCartAfter->execute($observerMock);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'Empty subscription params for first product' => [
                'product1Sku' => 'product1-sku',
                'product2Sku' => 'product2-sku',
                'subscription1Params' => [],
                'subscription1Option' => null,
                'subscription1Interval' => null,
                'subscription2Params' => [
                    SubscriptionOptionInterface::OPTION => 'onetime',
                    SubscriptionOptionInterface::INTERVAL => null,
                ],
                'subscription2Option' => 'onetime',
                'subscription2Interval' => null,
                'item1Warnings' => ['first warning'],
                'item2Warnings' => ['2nd warning', 'third warning'],
            ],
            'With subscription params for first product' => [
                'product1Sku' => 'product1sku',
                'product2Sku' => 'product2sku',
                'subscription1Params' => [
                    SubscriptionOptionInterface::OPTION => 'subscribe',
                    SubscriptionOptionInterface::INTERVAL => 'weekly',
                ],
                'subscription1Option' => 'subscribe',
                'subscription1Interval' => 'weekly',
                'subscription2Params' => [
                    SubscriptionOptionInterface::OPTION => 'onetime',
                    SubscriptionOptionInterface::INTERVAL => 'monthly',
                ],
                'subscription2Option' => 'onetime',
                'subscription2Interval' => 'monthly',
                'item1Warnings' => [],
                'item2Warnings' => [],
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Event\Observer
     */
    private function createObserverMock()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentItemId', 'getData', '__wakeup'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\ProductInterface
     */
    private function createPlatformProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)->getMock();
    }
}
