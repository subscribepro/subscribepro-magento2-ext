<?php

namespace Swarming\SubscribePro\Test\Unit\Observer\CheckoutCart;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Api\Data\ProductInterface;
use Magento\Framework\App\State as AppState;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Model\Config\General as ConfigGeneral;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater as SubscriptionOptionUpdater;
use Swarming\SubscribePro\Observer\CheckoutCart\AddProductToCartAfter;
use Swarming\SubscribePro\Platform\Manager\Product as ProductManager;
use Swarming\SubscribePro\Helper\Product as ProductHelper;
use Swarming\SubscribePro\Helper\QuoteItem as QuoteItemHelper;
use Magento\Quote\Model\Quote\Item as QuoteItem;

class AddProductToCartAfterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Observer\CheckoutCart\AddProductToCartAfter
     */
    protected $addProductToCartAfter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $configGeneralMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Quote\SubscriptionOption\Updater
     */
    protected $subscriptionOptionUpdaterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Manager\Product
     */
    protected $platformProductManagerMock;

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

    protected function setUp()
    {
        $this->configGeneralMock = $this->getMockBuilder(ConfigGeneral::class)
            ->disableOriginalConstructor()->getMock();
        $this->subscriptionOptionUpdaterMock = $this->getMockBuilder(SubscriptionOptionUpdater::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformProductManagerMock = $this->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->productHelperMock = $this->getMockBuilder(ProductHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->appStateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()->getMock();
        $this->quoteItemHelperMock = $this->getMockBuilder(QuoteItemHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)->getMock();

        $this->addProductToCartAfter = new AddProductToCartAfter(
            $this->configGeneralMock,
            $this->platformProductManagerMock,
            $this->subscriptionOptionUpdaterMock,
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

        $this->configGeneralMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->platformProductManagerMock->expects($this->never())->method('getProduct');
        $this->subscriptionOptionUpdaterMock->expects($this->never())->method('update');

        $this->addProductToCartAfter->execute($observerMock);
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

        $this->configGeneralMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(false);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionParams')
            ->with($quoteItemMock)
            ->willReturn($subscriptionParams);

        $this->platformProductManagerMock->expects($this->never())->method('getProduct');
        $this->subscriptionOptionUpdaterMock->expects($this->never())->method('update');

        $this->addProductToCartAfter->execute($observerMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage platform error
     */
    public function testFailToExecuteIfNoPlatformProductInDeveloperMode()
    {
        $exception = new NoSuchEntityException(__('platform error'));

        $subscriptionParams = ['params'];
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

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote_item')
            ->willReturn($quoteItemMock);

        $this->configGeneralMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionParams')
            ->with($quoteItemMock)
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

        $this->addProductToCartAfter->execute($observerMock);
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

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote_item')
            ->willReturn($quoteItemMock);

        $this->configGeneralMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionParams')
            ->with($quoteItemMock)
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

        $this->addProductToCartAfter->execute($observerMock);
    }

    /**
     * @return array
     */
    public function failToExecuteIfNoPlatformProductDataProvider()
    {
        return [
            'Default mode' => [
                'sku' => 'product sku',
                'exception' => new NoSuchEntityException(__('log message')),
                'message' => 'log message',
                'subscriptionParams' => ['params'],
                'appMode' => AppState::MODE_DEFAULT
            ],
            'Production mode' => [
                'sku' => 'sku',
                'exception' => new NoSuchEntityException(__('error message')),
                'message' => 'error message',
                'subscriptionParams' => ['params'],
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
     * @dataProvider testExecuteDataProvider
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

        $observerMock = $this->createObserverMock();
        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote_item')
            ->willReturn($quoteItemMock);

        $this->configGeneralMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->productHelperMock->expects($this->once())
            ->method('isSubscriptionEnabled')
            ->with($productMock)
            ->willReturn(true);

        $this->quoteItemHelperMock->expects($this->once())
            ->method('getSubscriptionParams')
            ->with($quoteItemMock)
            ->willReturn($subscriptionParams);

        $this->platformProductManagerMock->expects($this->once())
            ->method('getProduct')
            ->with($sku)
            ->willReturn($platformProductMock);

        $this->subscriptionOptionUpdaterMock->expects($this->once())
            ->method('update')
            ->with($quoteItemMock, $platformProductMock, $subscriptionOption, $subscriptionInterval)
            ->willReturn($warnings);

        $warningsMap = array_map(function($warning) {return [$warning];}, $warnings);
        $this->messageManagerMock->expects($this->exactly(count($warnings)))
            ->method('addWarningMessage')
            ->willReturnMap($warningsMap);

        $this->addProductToCartAfter->execute($observerMock);
    }

    /**
     * @return array
     */
    public function testExecuteDataProvider()
    {
        return [
            'Subscription params not set' => [
                'sku' => 'product sku',
                'subscriptionParams' => ['params'],
                'subscriptionOption' => null,
                'subscriptionInterval' => null,
                'warnings' => []
            ],
            'With subscription params' => [
                'sku' => 'sku',
                'subscriptionParams' => [
                    SubscriptionOptionInterface::OPTION => 'subscribe',
                    SubscriptionOptionInterface::INTERVAL => 'monthly',
                ],
                'subscriptionOption' => 'subscribe',
                'subscriptionInterval' => 'monthly',
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
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }
}
