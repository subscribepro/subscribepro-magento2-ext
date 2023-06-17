<?php

namespace Swarming\SubscribePro\Test\Unit\Ui\ComponentProvider;

use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Customer\Model\Options as CustomerOptions;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Ui\Component\Form\AttributeMapper;
use Swarming\SubscribePro\Ui\ComponentProvider\AddressAttributes;

class AddressAttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Ui\ComponentProvider\AddressAttributes
     */
    protected $uiAddressAttributes;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Model\AttributeMetadataDataProvider
     */
    protected $attributeMetadataDataProviderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Customer\Model\Options
     */
    protected $optionsMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Ui\Component\Form\AttributeMapper
     */
    protected $attributeMapperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Checkout\Block\Checkout\AttributeMerger
     */
    protected $mergerMock;

    protected function setUp(): void
    {
        $this->attributeMetadataDataProviderMock = $this->getMockBuilder(AttributeMetadataDataProvider::class)
            ->disableOriginalConstructor()->getMock();
        $this->optionsMock = $this->getMockBuilder(CustomerOptions::class)
            ->disableOriginalConstructor()->getMock();
        $this->attributeMapperMock = $this->getMockBuilder(AttributeMapper::class)
            ->disableOriginalConstructor()->getMock();
        $this->mergerMock = $this->getMockBuilder(AttributeMerger::class)
            ->disableOriginalConstructor()->getMock();

        $this->uiAddressAttributes = new AddressAttributes(
            $this->attributeMetadataDataProviderMock,
            $this->optionsMock,
            $this->attributeMapperMock,
            $this->mergerMock
        );
    }

    public function testGetElementsIfEmptyAttributes()
    {
        $this->attributeMetadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([]);

        $this->assertEquals([], $this->uiAddressAttributes->getElements());
    }

    public function testGetElementsIfIsUserDefinedAttribute()
    {
        $attributeCode = 'code';
        $attributeMock = $this->createEavAttributeMock();
        $attributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $attributeMock->expects($this->once())->method('getIsUserDefined')->willReturn(true);

        $this->attributeMetadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([$attributeMock]);

        $this->assertEquals([], $this->uiAddressAttributes->getElements());
    }

    /**
     * @param string $attributeCode
     * @param array $attributeMap
     * @param array $expectedAttributeMap
     * @dataProvider getElementsIfAttributeNotConvertedToSelectDataProvider
     */
    public function testGetElementsIfAttributeNotConvertedToSelect($attributeCode, $attributeMap, $expectedAttributeMap)
    {
        $attributeMock = $this->createEavAttributeMock();
        $attributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $attributeMock->expects($this->once())->method('getIsUserDefined')->willReturn(false);

        $this->attributeMetadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([$attributeMock]);

        $this->attributeMapperMock->expects($this->once())
            ->method('map')
            ->with($attributeMock)
            ->willReturn($attributeMap);

        $this->assertEquals([$attributeCode => $attributeMap], $this->uiAddressAttributes->getElements());
    }

    /**
     * @return array
     */
    public function getElementsIfAttributeNotConvertedToSelectDataProvider()
    {
        return [
            'Attribute without label' => [
                'attributeCode' => 'attr_code',
                'attributeMap' => ['key' => 'value'],
                'expectedAttributeMap' => ['key' => 'value']
            ],
            'Attribute with label' => [
                'attributeCode' => 'code',
                'attributeMap' => ['key' => 'value', 'label' => 'label text'],
                'expectedAttributeMap' => ['key' => 'value', 'label' => __('label text')]
            ]
        ];
    }

    public function testGetElementsIfAttributeIsPrefixAndNoOptions()
    {
        $attributeCode = 'prefix';
        $attributeMap = ['key' => 'value'];

        $attributeMock = $this->createEavAttributeMock();
        $attributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $attributeMock->expects($this->once())->method('getIsUserDefined')->willReturn(false);

        $this->attributeMetadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([$attributeMock]);

        $this->attributeMapperMock->expects($this->once())
            ->method('map')
            ->with($attributeMock)
            ->willReturn($attributeMap);

        $this->optionsMock->expects($this->once())
            ->method('getNamePrefixOptions')
            ->willReturn(false);

        $this->assertEquals([$attributeCode => $attributeMap], $this->uiAddressAttributes->getElements());
    }

    public function testGetElementsIfAttributeIsPrefixWithOptions()
    {
        $attributeCode = 'prefix';
        $options = ['key1' => 'value1', 'key2' => 'value2'];
        $attributeMap = ['key' => 'value'];
        $expectedAttributeMap = [
            'key' => 'value',
            'dataType' => 'select',
            'formElement' => 'select',
            'options' => [
                ['label' => 'value1', 'value' => 'key1'],
                ['label' => 'value2', 'value' => 'key2'],
            ]
        ];

        $attributeMock = $this->createEavAttributeMock();
        $attributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $attributeMock->expects($this->once())->method('getIsUserDefined')->willReturn(false);

        $this->attributeMetadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([$attributeMock]);

        $this->attributeMapperMock->expects($this->once())
            ->method('map')
            ->with($attributeMock)
            ->willReturn($attributeMap);

        $this->optionsMock->expects($this->once())
            ->method('getNamePrefixOptions')
            ->willReturn($options);

        $this->assertEquals([$attributeCode => $expectedAttributeMap], $this->uiAddressAttributes->getElements());
    }

    public function testGetElementsIfAttributeIsSuffixAndNoOptions()
    {
        $attributeCode = 'suffix';
        $attributeMap = ['key' => 'value'];

        $attributeMock = $this->createEavAttributeMock();
        $attributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $attributeMock->expects($this->once())->method('getIsUserDefined')->willReturn(false);

        $this->attributeMetadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([$attributeMock]);

        $this->attributeMapperMock->expects($this->once())
            ->method('map')
            ->with($attributeMock)
            ->willReturn($attributeMap);

        $this->optionsMock->expects($this->once())
            ->method('getNameSuffixOptions')
            ->willReturn(null);

        $this->assertEquals([$attributeCode => $attributeMap], $this->uiAddressAttributes->getElements());
    }

    public function testGetElementsIfAttributeIsSuffixWithOptions()
    {
        $attributeCode = 'suffix';
        $options = ['value' => 'text', 'another_value' => 'new_text'];
        $attributeMap = ['key' => 'value'];
        $expectedAttributeMap = [
            'key' => 'value',
            'dataType' => 'select',
            'formElement' => 'select',
            'options' => [
                ['label' => 'text', 'value' => 'value'],
                ['label' => 'new_text', 'value' => 'another_value'],
            ]
        ];

        $attributeMock = $this->createEavAttributeMock();
        $attributeMock->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $attributeMock->expects($this->once())->method('getIsUserDefined')->willReturn(false);

        $this->attributeMetadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([$attributeMock]);

        $this->attributeMapperMock->expects($this->once())
            ->method('map')
            ->with($attributeMock)
            ->willReturn($attributeMap);

        $this->optionsMock->expects($this->once())
            ->method('getNameSuffixOptions')
            ->willReturn($options);

        $this->assertEquals([$attributeCode => $expectedAttributeMap], $this->uiAddressAttributes->getElements());
    }

    public function testGetElementsIfMultipleAttributes()
    {
        $attributeCode1 = 'attr_1_code';
        $attributeMock1 = $this->createEavAttributeMock();
        $attributeMock1->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode1);
        $attributeMock1->expects($this->once())->method('getIsUserDefined')->willReturn(true);

        $attributeCode2 = 'attr_2_code';
        $attributeMap2 = ['attr2_key' => 'important_value', 'label' => 'text'];
        $expectedAttributeMap2 = ['attr2_key' => 'important_value', 'label' => __('text')];
        $attributeMock2 = $this->createEavAttributeMock();
        $attributeMock2->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode2);
        $attributeMock2->expects($this->once())->method('getIsUserDefined')->willReturn(false);

        $attributeCode3 = 'suffix';
        $options3 = ['key' => 'option_text'];
        $attributeMap3 = ['key' => 'value'];
        $expectedAttributeMap3 = [
            'key' => 'value',
            'dataType' => 'select',
            'formElement' => 'select',
            'options' => [
                ['label' => 'option_text', 'value' => 'key']
            ]
        ];
        $attributeMock3 = $this->createEavAttributeMock();
        $attributeMock3->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode3);
        $attributeMock3->expects($this->once())->method('getIsUserDefined')->willReturn(false);

        $expectedElements = [$attributeCode2 => $expectedAttributeMap2, $attributeCode3 => $expectedAttributeMap3];

        $this->attributeMetadataDataProviderMock->expects($this->once())
            ->method('loadAttributesCollection')
            ->with('customer_address', 'customer_register_address')
            ->willReturn([$attributeMock1, $attributeMock2, $attributeMock3]);

        $this->attributeMapperMock->expects($this->exactly(count($expectedElements)))
            ->method('map')
            ->willReturnMap([[$attributeMock2, $attributeMap2], [$attributeMock3, $attributeMap3]]);

        $this->optionsMock->expects($this->once())
            ->method('getNameSuffixOptions')
            ->willReturn($options3);

        $this->assertEquals($expectedElements, $this->uiAddressAttributes->getElements());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Eav\Api\Data\AttributeInterface
     */
    private function createEavAttributeMock()
    {
        return $this->getMockBuilder(AttributeInterface::class)->getMock();
    }
}
