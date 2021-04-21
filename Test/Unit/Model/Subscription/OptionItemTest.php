<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Swarming\SubscribePro\Model\Subscription\OptionItem;

class OptionItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Subscription\OptionItem
     */
    protected $subscriptionOptionItem;

    protected function setUp(): void
    {
        $this->subscriptionOptionItem = new OptionItem();
    }

    public function testAddOption()
    {
        $existingOptionData = ['existing option data'];
        $code1 = 'code1';
        $code2 = 'code2';

        $newOptionMock = $this->createOptionMock();
        $newOptionMock->expects($this->once())
            ->method('setData')
            ->with(OptionItem::ITEM, $this->subscriptionOptionItem);
        $newOptionMock->expects($this->once())->method('addData')->with($existingOptionData);
        $newOptionMock->expects($this->any())->method('getCode')->willReturn($code1);

        $existingOptionMock = $this->createOptionMock();
        $existingOptionMock->expects($this->once())
            ->method('setData')
            ->with(OptionItem::ITEM, $this->subscriptionOptionItem);
        $existingOptionMock->expects($this->once())->method('getData')->willReturn($existingOptionData);
        $existingOptionMock->expects($this->any())->method('getCode')->willReturn($code1);

        $option2Mock = $this->createOptionMock();
        $option2Mock->expects($this->once())
            ->method('setData')
            ->with(OptionItem::ITEM, $this->subscriptionOptionItem);
        $option2Mock->expects($this->any())->method('getCode')->willReturn($code2);

        $result = [$newOptionMock, $option2Mock];

        $this->subscriptionOptionItem->addOption($newOptionMock);
        $this->subscriptionOptionItem->addOption($existingOptionMock);
        $this->subscriptionOptionItem->addOption($option2Mock);

        $this->assertEquals(
            $result,
            $this->subscriptionOptionItem->getOptions(),
            'Fail asserting that two options were added.'
        );
    }

    public function testGetOptionByCode()
    {
        $code = 'code1';
        $optionMock = $this->createOptionMock();
        $optionMock->expects($this->once())
            ->method('setData')
            ->with(OptionItem::ITEM, $this->subscriptionOptionItem);
        $optionMock->expects($this->any())->method('getCode')->willReturn($code);

        $this->assertNull($this->subscriptionOptionItem->getOptionByCode($code));

        $this->subscriptionOptionItem->addOption($optionMock);

        $this->assertSame(
            $optionMock,
            $this->subscriptionOptionItem->getOptionByCode($code),
            'Fail to get option by code.'
        );
    }

    public function testSetOptions()
    {
        $code1 = 'code1';
        $code2 = 'code2';

        $option1Mock = $this->createOptionMock();
        $option1Mock->expects($this->once())
            ->method('setData')
            ->with(OptionItem::ITEM, $this->subscriptionOptionItem);
        $option1Mock->expects($this->any())->method('getCode')->willReturn($code1);

        $option2Mock = $this->createOptionMock();
        $option1Mock->expects($this->once())
            ->method('setData')
            ->with(OptionItem::ITEM, $this->subscriptionOptionItem);
        $option2Mock->expects($this->any())->method('getCode')->willReturn($code2);

        $this->subscriptionOptionItem->setOptions([$option1Mock, $option2Mock]);

        $this->assertEquals(
            [$option1Mock, $option2Mock],
            $this->subscriptionOptionItem->getOptions(),
            'Fail asserting that two options were added.'
        );
    }

    public function testGetProductIfNoProduct()
    {
        $this->assertNull($this->subscriptionOptionItem->getProduct());
    }

    public function testGetProduct()
    {
        $code = 'my_code';
        $optionMock = $this->createOptionMock();
        $optionMock->expects($this->once())
            ->method('setData')
            ->with(OptionItem::ITEM, $this->subscriptionOptionItem);
        $optionMock->expects($this->any())->method('getCode')->willReturn($code);

        $optionsByCode = [$code => $optionMock];

        $productMock = $this->createProductMock();
        $productMock->expects($this->once())->method('setFinalPrice')->with(null);
        $productMock->expects($this->once())->method('setCustomOptions')->with($optionsByCode);

        $this->subscriptionOptionItem->addOption($optionMock);
        $this->subscriptionOptionItem->setData(OptionItem::PRODUCT, $productMock);

        $this->assertSame($productMock, $this->subscriptionOptionItem->getProduct());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Api\Data\ProductInterface
     */
    private function createProductMock()
    {
        return $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['setFinalPrice', 'setCustomOptions'])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface
     */
    private function createOptionMock()
    {
        return $this->getMockBuilder(OptionInterface::class)
            ->setMethods(['getCode', 'setData', 'getData', 'addData'])
            ->getMockForAbstractClass();
    }
}
