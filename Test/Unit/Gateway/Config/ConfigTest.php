<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Swarming\SubscribePro\Gateway\Config\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $vaultConfig;

    /**
     * @var string
     */
    protected $configPath;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->configPath = 'test/code';

        $this->vaultConfig = new Config(
            $this->scopeConfigMock,
            'code',
            'test/%s/%s'
        );
    }

    /**
     * @param bool $isActiveValue
     * @param bool $result
     * @dataProvider isActiveDataProvider
     */
    public function testIsActive($isActiveValue, $result)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->configPath . '/' . Config::KEY_ACTIVE, ScopeInterface::SCOPE_STORE)
            ->willReturn($isActiveValue);

        $this->assertEquals($result, $this->vaultConfig->isActive());
    }

    /**
     * @return array
     */
    public function isActiveDataProvider()
    {
        return [
            'Is active value is zero' => [
                'isActiveValue' => 0,
                'result' => false,
            ],
            'Is active value is 1' => [
                'isActiveValue' => 1,
                'result' => true,
            ],
            'Is active value is bool:return same value' => [
                'isActiveValue' => true,
                'result' => true,
            ]
        ];
    }

    /**
     * @param bool $hasVerification
     * @param bool $result
     * @dataProvider hasVerificationDataProvider
     */
    public function testHasVerification($hasVerification, $result)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->configPath . '/' . Config::KEY_CC_USE_CCV, ScopeInterface::SCOPE_STORE)
            ->willReturn($hasVerification);

        $this->assertEquals($result, $this->vaultConfig->hasVerification());
    }

    /**
     * @return array
     */
    public function hasVerificationDataProvider()
    {
        return [
            'HasVerification value is bool false' => [
                'hasVerification' => 0,
                'result' => false,
            ],
            'HasVerification value is bool true' => [
                'hasVerification' => 1,
                'result' => true
            ],
        ];
    }

    /**
     * @param string $ccTypes
     * @param array $result
     * @dataProvider getAvailableCardTypesDataProvider
     */
    public function testGetAvailableCardTypes($ccTypes, $result)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->configPath . '/' . Config::KEY_CC_TYPES, ScopeInterface::SCOPE_STORE)
            ->willReturn($ccTypes);

        $this->assertEquals($result, $this->vaultConfig->getAvailableCardTypes());
    }

    /**
     * @return array
     */
    public function getAvailableCardTypesDataProvider()
    {
        return [
            'Empty card types' => [
                'ccTypes' => '',
                'result' => [],
            ],
            'Not empty card types' => [
                'ccTypes' => 'type1,type2',
                'result' => ['type1', 'type2'],
            ],
        ];
    }

    /**
     * @param string $ccTypesMapper
     * @param array $result
     * @dataProvider getCcTypesMapperDataProvider
     */
    public function testGetCcTypesMapper($ccTypesMapper, $result)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->configPath . '/' . Config::KEY_CC_TYPES_MAPPER, ScopeInterface::SCOPE_STORE)
            ->willReturn($ccTypesMapper);

        $this->assertEquals($result, $this->vaultConfig->getCcTypesMapper());
    }

    /**
     * @return array
     */
    public function getCcTypesMapperDataProvider()
    {
        return [
            'CcTypesMapper is not array' => [
                'ccTypesMapper' => json_encode(''),
                'result' => [],
            ],
            'CcTypesMapper array' => [
                'ccTypesMapper' => json_encode(['type1', 'type3']),
                'result' => ['type1', 'type3'],
            ]
        ];
    }

    /**
     * @param string $cardType
     * @param array $ccTypesMapper
     * @param string $result
     * @dataProvider getMappedCcTypeDataProvider
     */
    public function testGetMappedCcType($cardType, $ccTypesMapper, $result)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->configPath . '/' . Config::KEY_CC_TYPES_MAPPER, ScopeInterface::SCOPE_STORE)
            ->willReturn(json_encode($ccTypesMapper));

        $this->assertEquals($result, $this->vaultConfig->getMappedCcType($cardType));
    }

    /**
     * @return array
     */
    public function getMappedCcTypeDataProvider()
    {
        return [
            'Card type not found in CcTypesMapper:return card type' => [
                'cardType' => 'type2',
                'ccTypesMapper' => ['type1', 'type3'],
                'result' => 'type2',
            ],
            'Card type found in CcTypesMapper:return mapped card type' => [
                'cardType' => 'searchType',
                'ccTypesMapper' => ['type1' => 'mappedType1', 'searchType' => 'mappedType'],
                'result' => 'mappedType',
            ]
        ];
    }
}
