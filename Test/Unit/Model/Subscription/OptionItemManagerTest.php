<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;
use Swarming\SubscribePro\Model\Subscription\OptionItemFactory;
use Swarming\SubscribePro\Model\Subscription\OptionItemManager;
use Swarming\SubscribePro\Helper\ProductOption as ProductOptionHelper;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Swarming\SubscribePro\Model\Subscription\OptionItem as SubscriptionOptionItem;
use Magento\Catalog\Model\Product\Type\AbstractType as ProductTypeInstance;

class OptionItemManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Subscription\OptionItemManager
     */
    protected $subscriptionOptionItemManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Subscription\OptionItemFactory
     */
    protected $subscriptionItemFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Helper\ProductOption
     */
    protected $productOptionHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor
     */
    protected $cartItemOptionProcessorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject\Factory
     */
    protected $objectFactoryMock;

    protected function setUp()
    {
        $this->subscriptionItemFactoryMock = $this->getMockBuilder(OptionItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)->getMock();
        $this->productOptionHelperMock = $this->getMockBuilder(ProductOptionHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->cartItemOptionProcessorMock = $this->getMockBuilder(CartItemOptionsProcessor::class)
            ->disableOriginalConstructor()->getMock();
        $this->objectFactoryMock = $this->getMockBuilder(DataObjectFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->subscriptionOptionItemManager = new OptionItemManager(
            $this->subscriptionItemFactoryMock,
            $this->productRepositoryMock,
            $this->productOptionHelperMock,
            $this->cartItemOptionProcessorMock,
            $this->objectFactoryMock
        );
    }

    public function testGetSubscriptionOptionItemIfProductNotFound()
    {
        $exception = $this->getMockBuilder(NoSuchEntityException::class)->disableOriginalConstructor()->getMock();

        $sku = 'product_sku';

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getProductSku')->willReturn($sku);

        $subscriptionItem = $this->createSubscriptionOptionItemMock();
        $subscriptionItem->expects($this->never())->method('setProduct');
        $subscriptionItem->expects($this->never())->method('setOptions');

        $this->subscriptionItemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriptionItem);

        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($sku)
            ->willThrowException($exception);

        $this->assertSame(
            $subscriptionItem,
            $this->subscriptionOptionItemManager->getSubscriptionOptionItem($subscriptionMock)
        );
    }

    /**
     * @param string $sku
     * @param string $storeCode
     * @param string $productTypeId
     * @param array $subscriptionItemOptions
     * @param int|array $buyRequest
     * @param array $buyRequestParams
     * @dataProvider getSubscriptionOptionItemIfBuyRequestNotObjectDataProvider
     */
    public function testGetSubscriptionOptionItemIfBuyRequestNotObject(
        $sku,
        $productTypeId,
        $subscriptionItemOptions,
        $buyRequest,
        $buyRequestParams
    ) {
        $cartItemMock = $this->createCartItemMock();
        $buyRequestObjectMock = $this->createDataObjectMock();

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getProductSku')->willReturn($sku);

        $productTypeInstanceMock = $this->createProductTypeInstanceMock();

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('unsetData')->with('_cache_instance_options_collection');
        $productMock->expects($this->once())->method('getTypeId')->willReturn($productTypeId);
        $productMock->expects($this->once())->method('getTypeInstance')->willReturn($productTypeInstanceMock);
        $productMock->expects($this->once())->method('getCustomOptions')->willReturn($subscriptionItemOptions);

        $productTypeInstanceMock->expects($this->once())
            ->method('processConfiguration')
            ->with($buyRequestObjectMock, $productMock, ProductTypeInstance::PROCESS_MODE_FULL);

        $subscriptionItem = $this->createSubscriptionOptionItemMock();
        $subscriptionItem->expects($this->once())->method('setProduct')->with($productMock);
        $subscriptionItem->expects($this->once())->method('setOptions')->with($subscriptionItemOptions);

        $this->subscriptionItemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriptionItem);

        $this->productOptionHelperMock->expects($this->once())
            ->method('getCartItem')
            ->willReturn($cartItemMock);

        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($sku)
            ->willReturn($productMock);

        $this->cartItemOptionProcessorMock->expects($this->once())
            ->method('getBuyRequest')
            ->with($productTypeId, $cartItemMock)
            ->willReturn($buyRequest);

        $this->cartItemOptionProcessorMock->expects($this->once())
            ->method('getBuyRequest')
            ->with($productTypeId, $cartItemMock)
            ->willReturn($buyRequest);

        $this->objectFactoryMock->expects($this->once())
            ->method('create')
            ->with($buyRequestParams)
            ->willReturn($buyRequestObjectMock);

        $this->assertSame(
            $subscriptionItem,
            $this->subscriptionOptionItemManager->getSubscriptionOptionItem($subscriptionMock)
        );
    }

    /**
     * @return array
     */
    public function getSubscriptionOptionItemIfBuyRequestNotObjectDataProvider()
    {
        return [
            'Buy request is int' => [
                'sku' => 'sku-201',
                'productTypeId' => 'simple',
                'subscriptionItemOptions' => ['options'],
                'buyRequest' => 15,
                'buyRequestParams' => ['qty' => 15],
            ],
            'Buy request is array' => [
                'sku' => '123-ff21',
                'productTypeId' => 'bundle_type_id',
                'subscriptionItemOptions' => ['key' => 'option_value'],
                'buyRequest' => ['key' => ['value']],
                'buyRequestParams' => ['key' => ['value']],
            ],
        ];
    }

    public function testGetSubscriptionOptionItemIfBuyRequestIsObject()
    {
        $sku = 'product-sku';
        $productTypeId = 'virtual';
        $subscriptionItemOptions = ['options'];

        $cartItemMock = $this->createCartItemMock();
        $buyRequest = $this->createDataObjectMock();

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getProductSku')->willReturn($sku);

        $productTypeInstanceMock = $this->createProductTypeInstanceMock();

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('unsetData')->with('_cache_instance_options_collection');
        $productMock->expects($this->once())->method('getTypeId')->willReturn($productTypeId);
        $productMock->expects($this->once())->method('getTypeInstance')->willReturn($productTypeInstanceMock);
        $productMock->expects($this->once())->method('getCustomOptions')->willReturn($subscriptionItemOptions);

        $productTypeInstanceMock->expects($this->once())
            ->method('processConfiguration')
            ->with($buyRequest, $productMock, ProductTypeInstance::PROCESS_MODE_FULL);

        $subscriptionItem = $this->createSubscriptionOptionItemMock();
        $subscriptionItem->expects($this->once())->method('setProduct')->with($productMock);
        $subscriptionItem->expects($this->once())->method('setOptions')->with($subscriptionItemOptions);

        $this->subscriptionItemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriptionItem);

        $this->productOptionHelperMock->expects($this->once())
            ->method('getCartItem')
            ->willReturn($cartItemMock);

        $this->productRepositoryMock->expects($this->once())
            ->method('get')
            ->with($sku)
            ->willReturn($productMock);

        $this->cartItemOptionProcessorMock->expects($this->once())
            ->method('getBuyRequest')
            ->with($productTypeId, $cartItemMock)
            ->willReturn($buyRequest);

        $this->cartItemOptionProcessorMock->expects($this->once())
            ->method('getBuyRequest')
            ->with($productTypeId, $cartItemMock)
            ->willReturn($buyRequest);

        $this->objectFactoryMock->expects($this->never())->method('create');

        $this->assertSame(
            $subscriptionItem,
            $this->subscriptionOptionItemManager->getSubscriptionOptionItem($subscriptionMock)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\Data\ProductInterface
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['unsetData', 'getTypeId', 'getTypeInstance', 'getCustomOptions'])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product\Type\AbstractType
     */
    private function createProductTypeInstanceMock()
    {
        return $this->getMockBuilder(ProductTypeInstance::class)
            ->disableOriginalConstructor()
            ->setMethods(['processConfiguration'])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartItemInterface
     */
    private function createCartItemMock()
    {
        return $this->getMockBuilder(CartItemInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\SubscriptionInterface
     */
    private function createSubscriptionMock()
    {
        return $this->getMockBuilder(SubscriptionInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Subscription\OptionItem
     */
    private function createSubscriptionOptionItemMock()
    {
        return $this->getMockBuilder(SubscriptionOptionItem::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject
     */
    private function createDataObjectMock()
    {
        return $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();
    }
}
