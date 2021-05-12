<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Swarming\SubscribePro\Gateway\Config\VaultConfig;

class VaultConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\VaultConfig
     */
    protected $vaultConfig;

    /**
     * @var string
     */
    protected $configPath;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->configPath = 'test/code';

        $this->vaultConfig = new VaultConfig(
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
    public function testIsActive(
        $isActiveValue,
        $result
    ) {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with($this->configPath . '/' . VaultConfig::KEY_ACTIVE, ScopeInterface::SCOPE_STORE)
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
}
