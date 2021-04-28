<?php

namespace Swarming\SubscribePro\Test\Unit\Platform;

use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Swarming\SubscribePro\Platform\SdkFactory;
use Swarming\SubscribePro\Platform\Platform;
use Swarming\SubscribePro\Model\Config\Platform as PlatformConfig;
use SubscribePro\Sdk as SubscribeProSdk;

class PlatformTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Platform
     */
    protected $platform;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\Platform
     */
    protected $platformConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\SdkFactory
     */
    protected $sdkFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var array
     */
    protected $config = [
        'key' => 'value',
        'logging_file_name' => 'config_file_name'
    ];

    protected function setUp(): void
    {
        $this->sdkFactoryMock = $this->getMockBuilder(SdkFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->platformConfigMock = $this->getMockBuilder(PlatformConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)->getMock();

        $this->platform = new Platform(
            $this->sdkFactoryMock,
            $this->platformConfigMock,
            $this->storeManagerMock,
            $this->config
        );
    }

    public function testGetSdk()
    {
        $websiteId = 12;
        $websiteCode = 'code';
        $expectedConfig = [
            'key' => 'value',
            'client_id' => '123',
            'client_secret' => 'clientSecret',
            'logging_enable' => true,
            'logging_file_name' => 'file_name',
            'base_url' => 'test_base_url'
        ];

        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)->getMock();
        $websiteMock->expects($this->exactly(2))->method('getCode')->willReturn($websiteCode);

        $sdkMock = $this->getMockBuilder(SubscribeProSdk::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platformConfigMock->expects($this->once())
            ->method('getBaseUrl')
            ->with($websiteCode)
            ->willReturn('test_base_url');
        $this->platformConfigMock->expects($this->once())
            ->method('getClientId')
            ->with($websiteCode)
            ->willReturn('123');
        $this->platformConfigMock->expects($this->once())
            ->method('getClientSecret')
            ->with($websiteCode)
            ->willReturn('clientSecret');
        $this->platformConfigMock->expects($this->once())
            ->method('isLogEnabled')
            ->with($websiteCode)
            ->willReturn(true);
        $this->platformConfigMock->expects($this->once())
            ->method('getLogFilename')
            ->with($websiteCode)
            ->willReturn('file_name');

        $this->storeManagerMock->expects($this->exactly(2))
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->sdkFactoryMock->expects($this->once())
            ->method('create')
            ->with(['config' => $expectedConfig])
            ->willReturn($sdkMock);

        $this->assertSame(
            $sdkMock,
            $this->platform->getSdk($websiteId),
            'Fail asserting new sdk instance was returned'
        );
        $this->assertSame(
            $sdkMock,
            $this->platform->getSdk($websiteId),
            'Fail asserting that the same sdk instance was returned'
        );
    }
}
