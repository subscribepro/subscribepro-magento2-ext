<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Quote\SubscriptionOption;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ProductOptionExtensionFactory;
use Magento\Quote\Api\Data\ProductOptionExtensionInterface;
use Magento\Quote\Api\Data\ProductOptionInterface;
use Magento\Quote\Model\Quote\ProductOptionFactory;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionOptionInterfaceFactory;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;
use Magento\Framework\DataObject\Factory as DataObjectFactory;

class OptionProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor
     */
    protected $optionProcessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject\Factory
     */
    protected $objectFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\ProductOptionFactory
     */
    protected $productOptionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\ProductOptionExtensionFactory
     */
    protected $extensionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\SubscriptionOptionInterfaceFactory
     */
    protected $subscriptionOptionFactoryMock;

    protected function setUp()
    {
        $this->objectFactoryMock = $this->getMockBuilder(DataObjectFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->productOptionFactoryMock = $this->getMockBuilder(ProductOptionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->extensionFactoryMock = $this->getMockBuilder(ProductOptionExtensionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->subscriptionOptionFactoryMock = $this->getMockBuilder(SubscriptionOptionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->optionProcessor = new OptionProcessor(
            $this->objectFactoryMock,
            $this->productOptionFactoryMock,
            $this->extensionFactoryMock,
            $this->subscriptionOptionFactoryMock
        );
    }

    public function testConvertToBuyRequestIfNoProductOption()
    {
        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->once())->method('getProductOption')->willReturn(null);

        $this->objectFactoryMock->expects($this->never())->method('create');

        $this->assertNull($this->optionProcessor->convertToBuyRequest($cartItemMock));
    }

    public function testConvertToBuyRequestIfNoExtensionAttributes()
    {
        $productOptionMock = $this->createProductOptionMock();
        $productOptionMock->expects($this->once())->method('getExtensionAttributes')->willReturn(null);

        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->exactly(2))->method('getProductOption')->willReturn($productOptionMock);

        $this->objectFactoryMock->expects($this->never())->method('create');

        $this->assertNull($this->optionProcessor->convertToBuyRequest($cartItemMock));
    }

    public function testConvertToBuyRequestIfNoSubscriptionOption()
    {
        $extensionAttributesMock = $this->createExtensionAttributesMock();
        $extensionAttributesMock->expects($this->once())
            ->method('getSubscriptionOption')
            ->willReturn(null);

        $productOptionMock = $this->createProductOptionMock();
        $productOptionMock->expects($this->exactly(2))
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);

        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->exactly(3))->method('getProductOption')->willReturn($productOptionMock);

        $this->objectFactoryMock->expects($this->never())->method('create');

        $this->assertNull($this->optionProcessor->convertToBuyRequest($cartItemMock));
    }

    /**
     * @param array $subscriptionOptions
     * @dataProvider convertToBuyRequestIfEmptySubscriptionOptionsDataProvider
     */
    public function testConvertToBuyRequestIfEmptySubscriptionOptions($subscriptionOptions)
    {
        $subscriptionOptionMock = $this->createSubscriptionOptionMock();
        $subscriptionOptionMock->expects($this->once())
            ->method('__toArray')
            ->willReturn($subscriptionOptions);

        $extensionAttributesMock = $this->createExtensionAttributesMock();
        $extensionAttributesMock->expects($this->exactly(2))
            ->method('getSubscriptionOption')
            ->willReturn($subscriptionOptionMock);

        $productOptionMock = $this->createProductOptionMock();
        $productOptionMock->expects($this->exactly(3))
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);

        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->exactly(4))->method('getProductOption')->willReturn($productOptionMock);

        $this->objectFactoryMock->expects($this->never())->method('create');

        $this->assertNull($this->optionProcessor->convertToBuyRequest($cartItemMock));
    }

    /**
     * @return array
     */
    public function convertToBuyRequestIfEmptySubscriptionOptionsDataProvider()
    {
        return [
            'Empty subscription options' => [
                'subscriptionOptions' => null,
            ],
            'Subscription options not array' => [
                'subscriptionOptions' => 'string',
            ],
            'Subscription options empty array' => [
                'subscriptionOptions' => 'string',
            ],
        ];
    }

    public function testConvertToBuyRequest()
    {
        $resultDataObject = $this->createDataObjectMock();

        $subscriptionOptions = ['array'];
        $subscriptionOptionMock = $this->createSubscriptionOptionMock();
        $subscriptionOptionMock->expects($this->once())
            ->method('__toArray')
            ->willReturn($subscriptionOptions);

        $extensionAttributesMock = $this->createExtensionAttributesMock();
        $extensionAttributesMock->expects($this->exactly(2))
            ->method('getSubscriptionOption')
            ->willReturn($subscriptionOptionMock);

        $productOptionMock = $this->createProductOptionMock();
        $productOptionMock->expects($this->exactly(3))
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);

        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->exactly(4))->method('getProductOption')->willReturn($productOptionMock);

        $this->objectFactoryMock->expects($this->once())
            ->method('create')
            ->with([OptionProcessor::KEY_SUBSCRIPTION_OPTION => $subscriptionOptions])
            ->willReturn($resultDataObject);

        $this->assertSame($resultDataObject, $this->optionProcessor->convertToBuyRequest($cartItemMock));
    }

    public function testProcessOptionsIfNoBuyRequest()
    {
        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn(null);
        $cartItemMock->expects($this->never())->method('setProductOption');

        $this->assertSame($cartItemMock, $this->optionProcessor->processOptions($cartItemMock));
    }

    /**
     * @param string $buyRequestValue
     * @dataProvider processOptionsIfNoSubscriptionOptionsDataProvider
     */
    public function testProcessOptionsIfNoSubscriptionOptions($buyRequestValue)
    {
        $buyRequestMock = $this->createBuyRequestMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->exactly(2))
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);
        $cartItemMock->expects($this->never())->method('setProductOption');

        $this->assertSame($cartItemMock, $this->optionProcessor->processOptions($cartItemMock));
    }

    /**
     * @return array
     */
    public function processOptionsIfNoSubscriptionOptionsDataProvider()
    {
        return [
            'BuyRequest value not array' => [
                'buyRequestValue' => '',
            ],
            'BuyRequest value is empty array' => [
                'buyRequestValue' => json_encode([]),
            ],
            'BuyRequest value without subscription option' => [
                'buyRequestValue' => json_encode(['key' => 'value']),
            ],
            'Subscription option not array' => [
                'buyRequestValue' => json_encode([
                    'key' => 'value',
                    OptionProcessor::KEY_SUBSCRIPTION_OPTION => 'string'
                ]),
            ],
        ];
    }

    public function testProcessOptionsIfNoProductOption()
    {
        $subscriptionOptions = ['options'];
        $buyRequestValue = json_encode([
            'key' => 'value',
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => $subscriptionOptions
        ]);
        $subscriptionOptionMock = $this->createSubscriptionOptionMock();

        $extensionAttributesMock = $this->createExtensionAttributesMock();
        $extensionAttributesMock->expects($this->once())
            ->method('setSubscriptionOption')
            ->with($subscriptionOptionMock);

        $productOptionMock = $this->createProductOptionMock();
        $productOptionMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);
        $productOptionMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->willReturn($extensionAttributesMock);

        $buyRequestMock = $this->createBuyRequestMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->exactly(2))
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);
        $cartItemMock->expects($this->once())
            ->method('getProductOption')
            ->willReturn(null);
        $cartItemMock->expects($this->once())
            ->method('setProductOption')
            ->with($productOptionMock);

        $this->subscriptionOptionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriptionOptionMock);

        $this->productOptionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($productOptionMock);

        $this->assertSame($cartItemMock, $this->optionProcessor->processOptions($cartItemMock));
    }

    public function testProcessOptionsIfNoExtensionAttributes()
    {
        $subscriptionOptions = ['options'];
        $buyRequestValue = json_encode([
            'key' => 'value',
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => $subscriptionOptions
        ]);
        $subscriptionOptionMock = $this->createSubscriptionOptionMock();

        $extensionAttributesMock = $this->createExtensionAttributesMock();
        $extensionAttributesMock->expects($this->once())
            ->method('setSubscriptionOption')
            ->with($subscriptionOptionMock);

        $productOptionMock = $this->createProductOptionMock();
        $productOptionMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $productOptionMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->willReturn($extensionAttributesMock);

        $buyRequestMock = $this->createBuyRequestMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->exactly(2))
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);
        $cartItemMock->expects($this->once())
            ->method('getProductOption')
            ->willReturn(null);
        $cartItemMock->expects($this->once())
            ->method('setProductOption')
            ->with($productOptionMock);

        $this->subscriptionOptionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriptionOptionMock);

        $this->productOptionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($productOptionMock);

        $this->extensionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($extensionAttributesMock);

        $this->assertSame($cartItemMock, $this->optionProcessor->processOptions($cartItemMock));
    }

    public function testProcessOptions()
    {
        $subscriptionOptions = ['options'];
        $buyRequestValue = json_encode([
            'key' => 'value',
            OptionProcessor::KEY_SUBSCRIPTION_OPTION => $subscriptionOptions
        ]);
        $subscriptionOptionMock = $this->createSubscriptionOptionMock();

        $extensionAttributesMock = $this->createExtensionAttributesMock();
        $extensionAttributesMock->expects($this->once())
            ->method('setSubscriptionOption')
            ->with($subscriptionOptionMock);

        $productOptionMock = $this->createProductOptionMock();
        $productOptionMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributesMock);
        $productOptionMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->willReturn($extensionAttributesMock);

        $buyRequestMock = $this->createBuyRequestMock();
        $buyRequestMock->expects($this->once())->method('getValue')->willReturn($buyRequestValue);

        $cartItemMock = $this->createCartItemMock();
        $cartItemMock->expects($this->exactly(2))
            ->method('getOptionByCode')
            ->with('info_buyRequest')
            ->willReturn($buyRequestMock);
        $cartItemMock->expects($this->once())
            ->method('getProductOption')
            ->willReturn($productOptionMock);
        $cartItemMock->expects($this->once())
            ->method('setProductOption')
            ->with($productOptionMock);

        $this->subscriptionOptionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($subscriptionOptionMock);

        $this->productOptionFactoryMock->expects($this->never())->method('create');
        $this->extensionFactoryMock->expects($this->never())->method('create');

        $this->assertSame($cartItemMock, $this->optionProcessor->processOptions($cartItemMock));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartItemInterface
     */
    private function createCartItemMock()
    {
        return $this->getMockBuilder(CartItemInterface::class)
            ->setMethods(['getOptionByCode', 'getProductOption', 'setProductOption'])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\ProductOptionInterface
     */
    private function createProductOptionMock()
    {
        return $this->getMockBuilder(ProductOptionInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject
     */
    private function createDataObjectMock()
    {
        return $this->getMockBuilder(DataObject::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject
     */
    private function createBuyRequestMock()
    {
        return $this->getMockBuilder(DataObject::class)
            ->setMethods(['getValue'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\ProductOptionExtensionInterface
     */
    private function createExtensionAttributesMock()
    {
        return $this->getMockBuilder(ProductOptionExtensionInterface::class)
            ->setMethods(['getSubscriptionOption', 'setSubscriptionOption'])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\SubscriptionOptionInterface
     */
    private function createSubscriptionOptionMock()
    {
        return $this->getMockBuilder(SubscriptionOptionInterface::class)
            ->setMethods(['__toArray'])
            ->getMockForAbstractClass();
    }
}
