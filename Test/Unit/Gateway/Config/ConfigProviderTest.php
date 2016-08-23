<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Config;

use SubscribePro\Tools\Config as PlatformConfig;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Model\CcConfig
     */
    protected $ccConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Model\CcConfigProvider
     */
    protected $ccConfigProviderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Tool\Config
     */
    protected $platformConfigToolMock;
    
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\ConfigProvider
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->configMock = $this->getMockBuilder('Swarming\SubscribePro\Gateway\Config\Config')
            ->disableOriginalConstructor()->getMock();
        $this->ccConfigMock = $this->getMockBuilder('Magento\Payment\Model\CcConfig')
            ->disableOriginalConstructor()->getMock();
        $this->ccConfigProviderMock = $this->getMockBuilder('Magento\Payment\Model\CcConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $this->platformConfigToolMock = $this->getMockBuilder('Swarming\SubscribePro\Platform\Tool\Config')
            ->disableOriginalConstructor()->getMock();
        
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->configProvider = $objectManagerHelper->getObject(
            'Swarming\SubscribePro\Gateway\Config\ConfigProvider',
            [
                'config' => $this->configMock,
                'ccConfig' => $this->ccConfigMock,
                'ccConfigProvider' => $this->ccConfigProviderMock,
                'platformConfigTool' => $this->platformConfigToolMock,
            ]
        );
    }

    /**
     * @param bool $isActive
     * @param string $environmentKey
     * @param array $availableCardTypes
     * @param array $ccAvailableTypes
     * @param array $ccTypesMapper
     * @param bool $hasVerification
     * @param string $cvvImageUrl
     * @param array $icons
     * @param array $result
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig(
        $isActive, 
        $environmentKey, 
        $availableCardTypes,
        $ccAvailableTypes,
        $ccTypesMapper, 
        $hasVerification, 
        $cvvImageUrl, 
        $icons, 
        $result
    ) {
        $this->configMock->expects($this->once())->method('isActive')->willReturn($isActive);
        $this->configMock->expects($this->once())
            ->method('getAvailableCardTypes')
            ->willReturn($availableCardTypes);
        $this->configMock->expects($this->once())->method('getCcTypesMapper')->willReturn($ccTypesMapper);
        $this->configMock->expects($this->once())->method('hasVerification')->willReturn($hasVerification);
        
        $this->platformConfigToolMock->expects($this->once())
            ->method('getConfig')
            ->with(PlatformConfig::CONFIG_TRANSPARENT_REDIRECT_ENVIRONMENT_KEY)
            ->willReturn($environmentKey);

        $this->ccConfigMock->expects($this->once())->method('getCcAvailableTypes')->willReturn($ccAvailableTypes);
        $this->ccConfigMock->expects($this->once())->method('getCvvImageUrl')->willReturn($cvvImageUrl);
        
        $this->ccConfigProviderMock->expects($this->once())->method('getIcons')->willReturn($icons);
        
        $this->assertEquals($result, $this->configProvider->getConfig());
    }

    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            'No available card types' => [
                'isActive' => true,
                'environmentKey' => 'key',
                'availableCardTypes' => [],
                'ccAvailableTypes' => ['visa' => 'info'],
                'ccTypesMapper' => ['type mapper'],
                'hasVerification' => false,
                'cvvImageUrl' => 'image/url',
                'icons' => ['icon1'],
                'result' => [
                    'vaultCode' => ConfigProvider::VAULT_CODE,
                    'isActive' => true,
                    'environmentKey' => 'key',
                    'availableCardTypes' => ['visa' => 'info'],
                    'ccTypesMapper' => ['type mapper'],
                    'hasVerification' => false,
                    'cvvImageUrl' => 'image/url',
                    'icons' => ['icon1']
                ],
            ],
            'Available cc types from config and ccConfig not match' => [
                'isActive' => false,
                'environmentKey' => 'environment key',
                'availableCardTypes' => ['mastercard'],
                'ccAvailableTypes' => ['visa' => 'info'],
                'ccTypesMapper' => ['mapper type'],
                'hasVerification' => true,
                'cvvImageUrl' => 'image/url/1',
                'icons' => ['icon2'],
                'result' => [
                    'vaultCode' => ConfigProvider::VAULT_CODE,
                    'isActive' => false,
                    'environmentKey' => 'environment key',
                    'availableCardTypes' => [],
                    'ccTypesMapper' => ['mapper type'],
                    'hasVerification' => true,
                    'cvvImageUrl' => 'image/url/1',
                    'icons' => ['icon2'],
                ],
            ],
            'With available card types' => [
                'isActive' => true,
                'environmentKey' => 'key',
                'availableCardTypes' => ['mastercard', 'visa'],
                'ccAvailableTypes' => ['visa' => 'info', 'fake card' => 'info'],
                'ccTypesMapper' => ['type mapper'],
                'hasVerification' => false,
                'cvvImageUrl' => 'image/url',
                'icons' => ['icon1'],
                'result' => [
                    'vaultCode' => ConfigProvider::VAULT_CODE,
                    'isActive' => true,
                    'environmentKey' => 'key',
                    'availableCardTypes' => ['visa' => 'info'],
                    'ccTypesMapper' => ['type mapper'],
                    'hasVerification' => false,
                    'cvvImageUrl' => 'image/url',
                    'icons' => ['icon1']
                ],
            ],
        ];
    }
}
