<?php

namespace Swarming\SubscribePro\Test\Unit\Helper;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ProductOptionInterface;
use Swarming\SubscribePro\Api\Data\SubscriptionInterface;
use Swarming\SubscribePro\Helper\ProductOption;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor;

class ProductOptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Helper\ProductOption
     */
    protected $productOptionHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $reflectionObjectProcessorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Webapi\ServiceInputProcessor
     */
    protected $inputProcessorMock;

    protected function setUp(): void
    {
        $this->reflectionObjectProcessorMock = $this->getMockBuilder(DataObjectProcessor::class)
            ->disableOriginalConstructor()->getMock();
        $this->inputProcessorMock = $this->getMockBuilder(ServiceInputProcessor::class)
            ->disableOriginalConstructor()->getMock();

        $this->productOptionHelper = new ProductOption(
            $this->reflectionObjectProcessorMock,
            $this->inputProcessorMock
        );
    }

    public function testGetCartItem()
    {
        $sku = 'product-sku-21';
        $qty = 231;
        $productOption = ['product-option'];
        $cartItemMock = $this->createCartItemMock();

        $cartItemData = [
            CartItemInterface::KEY_SKU => $sku,
            CartItemInterface::KEY_QTY => $qty,
            CartItemInterface::KEY_PRODUCT_OPTION => $productOption
        ];

        $subscriptionMock = $this->createSubscriptionMock();
        $subscriptionMock->expects($this->once())->method('getProductSku')->willReturn($sku);
        $subscriptionMock->expects($this->once())->method('getQty')->willReturn($qty);
        $subscriptionMock->expects($this->once())->method('getProductOption')->willReturn($productOption);

        $this->inputProcessorMock->expects($this->once())
            ->method('convertValue')
            ->with($cartItemData, CartItemInterface::class)
            ->willReturn($cartItemMock);

        $this->assertSame(
            $cartItemMock,
            $this->productOptionHelper->getCartItem($subscriptionMock)
        );
    }

    public function testGetProductOptionIfNoProductOptionInQuoteItem()
    {
        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getProductOption')->willReturn(null);

        $this->reflectionObjectProcessorMock->expects($this->never())->method('buildOutputDataArray');

        $this->assertEquals([], $this->productOptionHelper->getProductOption($quoteItemMock));
    }

    /**
     * @param array $productOptions
     * @param array $result
     * @dataProvider getProductOptionDataProvider
     */
    public function testGetProductOption($productOptions, $result)
    {
        $productOptionMock = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->getMock();

        $quoteItemMock = $this->createQuoteItemMock();
        $quoteItemMock->expects($this->once())->method('getProductOption')->willReturn($productOptionMock);

        $this->reflectionObjectProcessorMock->expects($this->once())
            ->method('buildOutputDataArray')
            ->with($productOptionMock, ProductOptionInterface::class)
            ->willReturn($productOptions);

        $this->assertEquals($result, $this->productOptionHelper->getProductOption($quoteItemMock));
    }

    /**
     * @return array
     */
    public function getProductOptionDataProvider()
    {
        return [
            'Empty product options' => [
                'productOptions' => [],
                'result' => []
            ],
            'Empty extension attributes: unset extension attributes' => [
                'productOptions' => [
                    ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY => [],
                    'key' => 'value'
                ],
                'result' => ['key' => 'value']
            ],
            'Extension attributes contain only subscription option: unset extension attributes' => [
                'productOptions' => [
                    ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY => [
                        OptionProcessor::KEY_SUBSCRIPTION_OPTION => ['value']
                    ],
                    'key' => ['value']
                ],
                'result' => ['key' => ['value']]
            ],
            'With subscription option: unset subscription option' => [
                'productOptions' => [
                    ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY => [
                        OptionProcessor::KEY_SUBSCRIPTION_OPTION => ['value'],
                        'extension_key' => 'extension_value'
                    ],
                    'key' => ['value']
                ],
                'result' => [
                    ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY => [
                        'extension_key' => 'extension_value'
                    ],
                    'key' => ['value']
                ]
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item
     */
    private function createQuoteItemMock()
    {
        return $this->getMockBuilder(QuoteItem::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Api\Data\SubscriptionInterface
     */
    private function createSubscriptionMock()
    {
        return $this->getMockBuilder(SubscriptionInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartItemInterface
     */
    private function createCartItemMock()
    {
        return $this->getMockBuilder(CartItemInterface::class)->getMock();
    }
}
