<?php

namespace Swarming\SubscribePro\Test\Unit\Ui\DataProvider\Product\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription;
use Magento\Ui\Component\Form;

class SubscriptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription
     */
    protected $uiSubscriptionModifier;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Locator\LocatorInterface
     */
    protected $locatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Stdlib\ArrayManager
     */
    protected $arrayManagerMock;

    protected function setUp(): void
    {
        $this->locatorMock = $this->getMockBuilder(LocatorInterface::class)->getMock();
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->uiSubscriptionModifier = new Subscription(
            $this->locatorMock,
            $this->arrayManagerMock,
            $this->scopeConfigMock
        );
    }

    /**
     * @param array $data
     * @param int $productId
     * @param array $result
     * @dataProvider modifyDataProvider
     */
    public function testModifyData($data, $productId, $result)
    {
        $productMock = $this->getMockBuilder(ProductInterface::class)->getMock();
        $productMock->expects($this->once())->method('getId')->willReturn($productId);

        $this->locatorMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $this->assertEquals($result, $this->uiSubscriptionModifier->modifyData($data));
    }

    /**
     * @return array
     */
    public function modifyDataProvider()
    {
        return [
            'Attribute subscription_enabled not set in data' => [
                'data' => [
                    'key' => 'value',
                ],
                'productId' => 123,
                'result' => [
                    'key' => 'value',
                    123 => [
                        Subscription::DATA_SOURCE_DEFAULT => [
                            Subscription::SUBSCRIPTION_ENABLED => '0'
                        ]
                    ]
                ],
            ],
            'Attribute subscription_enabled is in data' => [
                'data' => [
                    'some' => 'data',
                    234 => [
                        Subscription::DATA_SOURCE_DEFAULT => [
                            Subscription::SUBSCRIPTION_ENABLED => '1'
                        ]
                    ]
                ],
                'productId' => 234,
                'result' => [
                    'some' => 'data',
                    234 => [
                        Subscription::DATA_SOURCE_DEFAULT => [
                            Subscription::SUBSCRIPTION_ENABLED => '1'
                        ]
                    ]
                ],
            ],
        ];
    }

    public function testModifyMeta()
    {
        $meta = ['meta'];
        $switcherConfig = [
            'dataType' => Form\Element\DataType\Number::NAME,
            'formElement' => Form\Element\Checkbox::NAME,
            'componentType' => Form\Field::NAME,
            'prefer' => 'toggle',
            'valueMap' => [
                'true' => '1',
                'false' => '0'
            ],
        ];
        $subscriptionEnabledPath = 'subscription/path';
        $mergePath = $subscriptionEnabledPath . Subscription::META_CONFIG_PATH;
        $result = ['modified meta'];

        $this->arrayManagerMock->expects($this->once())
            ->method('findPath')
            ->with(Subscription::SUBSCRIPTION_ENABLED, $meta, null, 'children')
            ->willReturn($subscriptionEnabledPath);
        $this->arrayManagerMock->expects($this->once())
            ->method('merge')
            ->with($mergePath, $meta, $switcherConfig)
            ->willReturn($result);

        $this->assertEquals($result, $this->uiSubscriptionModifier->modifyMeta($meta));
    }
}
