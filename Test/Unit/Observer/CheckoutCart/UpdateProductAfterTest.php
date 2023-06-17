<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\CheckoutCart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Helper\Product as ProductHelper;
use Swarming\SubscribePro\Model\Config\General as GeneralConfig;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater as SubscriptionOptionUpdater;
use Swarming\SubscribePro\Observer\CheckoutCart\UpdateProductAfter;
use Swarming\SubscribePro\Platform\Manager\Product as ProductManager;

class UpdateProductAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\CheckoutCart\UpdateProductAfter
     */
    protected $updateProductAfter;

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
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\RequestInterface
     */
    protected $requestMock;

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
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)->getMock();

        $this->updateProductAfter = new UpdateProductAfter(
            $this->generalConfigMock,
            $this->platformProductManagerMock,
            $this->subscriptionOptionUpdaterMock,
            $this->productRepositoryMock,
            $this->productHelperMock,
            $this->messageManagerMock,
            $this->appStateMock,
            $this->loggerMock,
            $this->requestMock
        );
    }

    public function testExecuteIfSubscribeProDisabled()
    {
        $observerMock = $this->createObserverMock();

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->platformProductManagerMock->expects($this->never())->method('getProduct');
        $this->subscriptionOptionUpdaterMock->expects($this->never())->method('update');

        $this->updateProductAfter->execute($observerMock);
    }

    public function testExecuteIfNotSubscriptionProduct()
    {
        $productMock = $this->createProductMock();
        $subscriptionParams = ['params'];

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote_item')
            ->willReturn($quoteItemMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(false);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(OptionProcessor::KEY_SUBSCRIPTION_OPTION)
            ->willReturn($subscriptionParams);

        $this->platformProductManagerMock->expects($this->never())->method('getProduct');
        $this->subscriptionOptionUpdaterMock->expects($this->never())->method('update');

        $this->updateProductAfter->execute($observerMock);
    }

    public function testExecuteIfProductHasParent()
    {
        $productParentSku = 'product-sku';
        $subscriptionOption = 'some_option';
        $subscriptionInterval = 'interval';
        $subscriptionParams = [
            SubscriptionOptionInterface::OPTION => $subscriptionOption,
            SubscriptionOptionInterface::INTERVAL => $subscriptionInterval
        ];

        $platformProductMock = $this->getMockBuilder(ProductInterface::class)->getMock();

        $productParentMock = $this->createProductMock();
        $productParentMock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($productParentSku);

        $productMock = $this->createProductMock();

        $quoteItemParentMock = $this->createQuoteItemMock();
        $quoteItemParentMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($productParentMock);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);
        $quoteItemMock->expects($this->atLeastOnce())->method('getParentItem')->willReturn($quoteItemParentMock);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote_item')
            ->willReturn($quoteItemMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productParentMock)
            ->willReturn(true);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(OptionProcessor::KEY_SUBSCRIPTION_OPTION)
            ->willReturn($subscriptionParams);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($productParentSku)
            ->willReturn($platformProductMock);

        $this->subscriptionOptionUpdaterMock->expects($this->once())
            ->method('update')
            ->with($quoteItemMock, $platformProductMock, $subscriptionOption, $subscriptionInterval)
            ->willReturn([]);

        $this->updateProductAfter->execute($observerMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage error message
     */
    public function testFailToExecuteIfNoPlatformProductInDeveloperMode()
    {
        $exception = new NoSuchEntityException(__('error message'));

        $subscriptionParams = ['subscription params'];
        $sku = 'product_sku';
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($sku);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getParentItem')->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote_item')
            ->willReturn($quoteItemMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(OptionProcessor::KEY_SUBSCRIPTION_OPTION)
            ->willReturn($subscriptionParams);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(true);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willThrowException($exception);

        $this->appStateMock->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_DEVELOPER);

        $this->subscriptionOptionUpdaterMock->expects($this->never())->method('update');

        $this->updateProductAfter->execute($observerMock);
    }

    /**
     * @param string $sku
     * @param \Exception $exception
     * @param string $message
     * @param array $subscriptionParams
     * @param string $appMode
     * @dataProvider failToExecuteIfNoPlatformProductDataProvider
     */
    public function testFailToExecuteIfNoPlatformProduct($sku, $exception, $message, $subscriptionParams, $appMode)
    {
        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($sku);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getParentItem')->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote_item')
            ->willReturn($quoteItemMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(OptionProcessor::KEY_SUBSCRIPTION_OPTION)
            ->willReturn($subscriptionParams);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(true);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willThrowException($exception);

        $this->appStateMock->expects($this->once())
            ->method('getMode')
            ->willReturn($appMode);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($message);

        $this->subscriptionOptionUpdaterMock->expects($this->never())->method('update');

        $this->updateProductAfter->execute($observerMock);
    }

    /**
     * @return array
     */
    public function failToExecuteIfNoPlatformProductDataProvider()
    {
        return [
            'Default mode' => [
                'sku' => 'product sku',
                'exception' => new NoSuchEntityException(__('some log message')),
                'message' => 'some log message',
                'subscriptionParams' => ['params', 'subscription'],
                'appMode' => AppState::MODE_DEFAULT
            ],
            'Production mode' => [
                'sku' => 'sku',
                'exception' => new NoSuchEntityException(__('not found message')),
                'message' => 'not found message',
                'subscriptionParams' => ['subscription', 'params'],
                'appMode' => AppState::MODE_PRODUCTION
            ]
        ];
    }

    /**
     * @param string $sku
     * @param array $subscriptionParams
     * @param null|string $subscriptionOption
     * @param null|string $subscriptionInterval
     * @param array $warnings
     * @dataProvider executeDataProvider
     */
    public function testExecute($sku, $subscriptionParams, $subscriptionOption, $subscriptionInterval, $warnings)
    {
        $platformProductMock = $this->getMockBuilder(ProductInterface::class)->getMock();

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())
            ->method('getData')
            ->with(ProductInterface::SKU)
            ->willReturn($sku);

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);
        $quoteItemMock->expects($this->once())->method('getParentItem')->willReturn(null);

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote_item')
            ->willReturn($quoteItemMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(true);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with(OptionProcessor::KEY_SUBSCRIPTION_OPTION)
            ->willReturn($subscriptionParams);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willReturn($platformProductMock);

        $this->subscriptionOptionUpdaterMock->expects($this->once())
            ->method('update')
            ->with($quoteItemMock, $platformProductMock, $subscriptionOption, $subscriptionInterval)
            ->willReturn($warnings);

        $warningsMap = array_map(function ($warning) {
            return [$warning];
        }, $warnings);
        $this->messageManagerMock->expects($this->exactly(count($warnings)))
            ->method('addWarningMessage')
            ->willReturnMap($warningsMap);

        $this->updateProductAfter->execute($observerMock);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'Subscription params not set' => [
                'sku' => 'product sku',
                'subscriptionParams' => ['subscription params'],
                'subscriptionOption' => null,
                'subscriptionInterval' => null,
                'warnings' => []
            ],
            'With subscription params' => [
                'sku' => 'sku_23',
                'subscriptionParams' => [
                    SubscriptionOptionInterface::OPTION => 'some_option',
                    SubscriptionOptionInterface::INTERVAL => '3 days',
                ],
                'subscriptionOption' => 'some_option',
                'subscriptionInterval' => '3 days',
                'warnings' => ['first warning', 'second warning']
            ]
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
}
