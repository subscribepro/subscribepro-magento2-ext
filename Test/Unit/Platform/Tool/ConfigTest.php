<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Tool;

use Swarming\SubscribePro\Platform\Platform;
use SubscribePro\Tools\Config as ConfigTool;
use Swarming\SubscribePro\Platform\Storage\Config as ConfigStorage;
use Swarming\SubscribePro\Platform\Tool\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Tool\Config
     */
    protected $configTool;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Tools\Config
     */
    protected $platformConfigToolMock;
    
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Storage\Config
     */
    protected $configStorageMock;
    
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Platform
     */
    protected $platformMock;

    protected $name = 'test_name';

    protected function setUp()
    {
        $this->platformMock = $this->getMockBuilder(Platform::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->platformConfigToolMock = $this->getMockBuilder(ConfigTool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configStorageMock = $this->getMockBuilder(ConfigStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configTool = new Config(
            $this->platformMock,
            $this->name,
            $this->configStorageMock
        );
    }

    /**
     * @param int|null $websiteId
     * @param string|null $key
     * @param array $config
     * @param array|string $result
     * @dataProvider getConfigIfCachedDataProvider
     */
    public function testGetConfigIfCached($websiteId, $key, $config, $result)
    {
        $this->configStorageMock->expects($this->once())
            ->method('load')
            ->with($websiteId)
            ->willReturn($config);

        $this->platformMock->expects($this->never())->method('getSdk');
        $this->platformConfigToolMock->expects($this->never())->method('load');
        
        $this->assertEquals($result, $this->configTool->getConfig($key, $websiteId));
    }

    /**
     * @return array
     */
    public function getConfigIfCachedDataProvider()
    {
        return [
            'Without key' => [
                'websiteId' => 123,
                'key' => null,
                'config' => ['value'],
                'result' => ['value'],
            ],
            'With key:not found' => [
                'websiteId' => 24,
                'key' => 'config_key',
                'config' => ['config_key' => 'config_value'],
                'result' => 'config_value',
            ],
            'With key:value found' => [
                'websiteId' => 24,
                'key' => '111',
                'config' => ['111' => '222'],
                'result' => '222',
            ],
        ];
    }

    /**
     * @param int|null $websiteId
     * @param int $expectedWebsiteId
     * @param string|null $key
     * @param array $config
     * @param array|string $result
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig($websiteId, $expectedWebsiteId, $key, $config, $result)
    {
        $sdkMock = $this->getMockBuilder(\SubscribePro\Sdk::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sdkMock->expects($this->once())
            ->method('getTool')
            ->with($this->name)
            ->willReturn($this->platformConfigToolMock);

        $this->configStorageMock->expects($this->once())
            ->method('load')
            ->with($websiteId)
            ->willReturn(null);

        $this->platformMock->expects($this->once())
            ->method('getSdk')
            ->with($expectedWebsiteId)
            ->willReturn($sdkMock);

        $this->platformConfigToolMock->expects($this->once())
            ->method('load')
            ->willReturn($config);

        $this->assertEquals($result, $this->configTool->getConfig($key, $websiteId));
    }

    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            'With website Id' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
                'key' => 'key',
                'config' => [],
                'result' => null,
            ],
            'Without key' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
                'key' => null,
                'config' => ['result'],
                'result' => ['result'],
            ],
            'With key' => [
                'websiteId' => 12,
                'expectedWebsiteId' => 12,
                'key' => 'key',
                'config' => ['key' => 'value'],
                'result' => 'value',
            ],
        ];
    }
}
