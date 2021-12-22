<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Quote;

use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartItemInterface;
use Swarming\SubscribePro\Plugin\Quote\CartItemOptionsProcessor;
use Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor as SubscriptionOptionProcessor;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor as QuoteCartItemOptionsProcessor;

class CartItemOptionsProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Plugin\Quote\CartItemOptionsProcessor
     */
    protected $cartItemOptionsProcessorPlugin;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Quote\SubscriptionOption\OptionProcessor
     */
    protected $subscriptionOptionProcessorMock;

    protected function setUp(): void
    {
        $this->subscriptionOptionProcessorMock = $this->getMockBuilder(SubscriptionOptionProcessor::class)
            ->disableOriginalConstructor()->getMock();

        $this->cartItemOptionsProcessorPlugin = new CartItemOptionsProcessor(
            $this->subscriptionOptionProcessorMock
        );
    }

    public function testAroundGetBuyRequestIfNoSubscriptionBuyRequest()
    {
        $productType = 'simple';
        $cartItemMock = $this->createCartItemMock();
        $subjectMock = $this->createQuoteCartItemOptionsProcessorMock();

        $buyRequestMock = $this->createDataObjectMock();
        $buyRequestMock->expects($this->never())->method('addData');

        $proceed = $this->createAroundGetBuyRequestCallback($buyRequestMock);

        $this->subscriptionOptionProcessorMock->expects($this->once())
            ->method('convertToBuyRequest')
            ->with($cartItemMock)
            ->willReturn(null);

        $this->assertSame(
            $buyRequestMock,
            $this->cartItemOptionsProcessorPlugin->aroundGetBuyRequest(
                $subjectMock,
                $proceed,
                $productType,
                $cartItemMock
            )
        );
    }

    public function testAroundGetBuyRequestIfNoBuyRequest()
    {
        $productType = 'simple';
        $cartItemMock = $this->createCartItemMock();
        $subjectMock = $this->createQuoteCartItemOptionsProcessorMock();

        $subscriptionBuyRequestMock = $this->createDataObjectMock();

        $proceed = $this->createAroundGetBuyRequestCallback(null);

        $this->subscriptionOptionProcessorMock->expects($this->once())
            ->method('convertToBuyRequest')
            ->with($cartItemMock)
            ->willReturn($subscriptionBuyRequestMock);

        $this->assertSame(
            $subscriptionBuyRequestMock,
            $this->cartItemOptionsProcessorPlugin->aroundGetBuyRequest(
                $subjectMock,
                $proceed,
                $productType,
                $cartItemMock
            )
        );
    }

    public function testAroundGetBuyRequestIfBuyRequestIsDataObject()
    {
        $productType = 'simple';
        $subscriptionData = ['data'];
        $cartItemMock = $this->createCartItemMock();
        $subjectMock = $this->createQuoteCartItemOptionsProcessorMock();

        $subscriptionBuyRequestMock = $this->createDataObjectMock();
        $subscriptionBuyRequestMock->expects($this->once())->method('getData')->willReturn($subscriptionData);

        $buyRequestMock = $this->createDataObjectMock();
        $buyRequestMock->expects($this->once())->method('addData')->with($subscriptionData);

        $proceed = $this->createAroundGetBuyRequestCallback($buyRequestMock);

        $this->subscriptionOptionProcessorMock->expects($this->once())
            ->method('convertToBuyRequest')
            ->with($cartItemMock)
            ->willReturn($subscriptionBuyRequestMock);

        $this->assertSame(
            $buyRequestMock,
            $this->cartItemOptionsProcessorPlugin->aroundGetBuyRequest(
                $subjectMock,
                $proceed,
                $productType,
                $cartItemMock
            )
        );
    }

    public function testAroundGetBuyRequestIfBuyRequestIsNumeric()
    {
        $productType = 'simple';
        $cartItemMock = $this->createCartItemMock();
        $subjectMock = $this->createQuoteCartItemOptionsProcessorMock();
        $buyRequest = 35;

        $subscriptionBuyRequestMock = $this->createDataObjectMock();
        $subscriptionBuyRequestMock->expects($this->once())
            ->method('setData')
            ->with('qty', $buyRequest);

        $proceed = $this->createAroundGetBuyRequestCallback($buyRequest);

        $this->subscriptionOptionProcessorMock->expects($this->once())
            ->method('convertToBuyRequest')
            ->with($cartItemMock)
            ->willReturn($subscriptionBuyRequestMock);

        $this->assertSame(
            $subscriptionBuyRequestMock,
            $this->cartItemOptionsProcessorPlugin->aroundGetBuyRequest(
                $subjectMock,
                $proceed,
                $productType,
                $cartItemMock
            )
        );
    }

    public function testAroundApplyCustomOptions()
    {
        $subjectMock = $this->createQuoteCartItemOptionsProcessorMock();
        $cartItemMock = $this->createCartItemMock();
        $proceed = function () use ($cartItemMock) {
            return $cartItemMock;
        };

        $this->subscriptionOptionProcessorMock->expects($this->once())
            ->method('processOptions')
            ->with($cartItemMock);

        $this->assertSame(
            $cartItemMock,
            $this->cartItemOptionsProcessorPlugin->aroundApplyCustomOptions($subjectMock, $proceed, $cartItemMock)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DataObject
     */
    private function createDataObjectMock()
    {
        return $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Api\Data\CartItemInterface
     */
    private function createCartItemMock()
    {
        return $this->getMockBuilder(CartItemInterface::class)->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor
     */
    private function createQuoteCartItemOptionsProcessorMock()
    {
        return $this->getMockBuilder(QuoteCartItemOptionsProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param int|\Magento\Framework\DataObject $buyRequest
     * @return callable
     */
    private function createAroundGetBuyRequestCallback($buyRequest)
    {
        return function ($productType, $cartItem) use ($buyRequest) {
            return $buyRequest;
        };
    }
}
