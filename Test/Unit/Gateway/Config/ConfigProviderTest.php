<?php

namespace Swarming\SubscribePro\Test\Unit\Gateway\Config;

use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcConfigProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use SubscribePro\Tools\Config as ConfigTool;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Model\Config\General as GeneralConfig;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;
use Swarming\SubscribePro\Platform\Tool\Config as PlatformConfigTool;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfigMock;

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
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerMock;

    protected function setUp(): void
    {
        $this->generalConfigMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->gatewayConfigMock = $this->getMockBuilder(GatewayConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ccConfigMock = $this->getMockBuilder(CcConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ccConfigProviderMock = $this->getMockBuilder(CcConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platformConfigToolMock = $this->getMockBuilder(PlatformConfigTool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = new ConfigProvider(
            $this->generalConfigMock,
            $this->gatewayConfigMock,
            $this->ccConfigMock,
            $this->ccConfigProviderMock,
            $this->platformConfigToolMock,
            $this->storeManagerMock
        );
    }

    public function testGetConfigIfModuleDisabled()
    {
        $result = [
            'vaultCode' => ConfigProvider::VAULT_CODE,
            'isActive' => false,
        ];
        $storeMock = $this->getMockBuilder(StoreInterface::class)->getMock();
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->generalConfigMock->expects($this->once())->method('isEnabled')->willReturn(false);

        $this->gatewayConfigMock->expects($this->never())->method('isActive');
        $this->gatewayConfigMock->expects($this->never())->method('getAvailableCardTypes');
        $this->gatewayConfigMock->expects($this->never())->method('getCcTypesMapper');
        $this->gatewayConfigMock->expects($this->never())->method('hasVerification');

        $this->platformConfigToolMock->expects($this->never())->method('getConfig');

        $this->ccConfigMock->expects($this->never())->method('getCcAvailableTypes');
        $this->ccConfigMock->expects($this->never())->method('getCvvImageUrl');

        $this->ccConfigProviderMock->expects($this->never())->method('getIcons');

        $this->assertEquals($result, $this->configProvider->getConfig());
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
        $storeMock = $this->getMockBuilder(StoreInterface::class)->getMock();
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->generalConfigMock->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->gatewayConfigMock->expects($this->once())->method('isActive')->willReturn($isActive);
        $this->gatewayConfigMock
            ->expects($this->once())
            ->method('getAvailableCardTypes')
            ->willReturn($availableCardTypes);
        $this->gatewayConfigMock->expects($this->once())->method('getCcTypesMapper')->willReturn($ccTypesMapper);
        $this->gatewayConfigMock->expects($this->once())->method('hasVerification')->willReturn($hasVerification);

        $this->platformConfigToolMock->expects($this->once())
            ->method('getConfig')
            ->with(ConfigTool::CONFIG_TRANSPARENT_REDIRECT_ENVIRONMENT_KEY)
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
