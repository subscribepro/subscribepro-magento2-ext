<?php

namespace Swarming\SubscribePro\Test\Unit\Platform;

use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use SubscribePro\SdkFactory;
use Swarming\SubscribePro\Platform\Platform;
use Swarming\SubscribePro\Model\Config\Platform as ConfigPlatform;

class PlatformTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Platform
     */
    protected $platform;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\Platform
     */
    protected $configPlatformMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\SdkFactory
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
        'logging_level' => 'error'
    ];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Address
     */
    protected $platformAddressServiceMock;

    protected function setUp()
    {
        $this->sdkFactoryMock = $this->getMockBuilder(SdkFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->configPlatformMock = $this->getMockBuilder(ConfigPlatform::class)
            ->disableOriginalConstructor()->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)->getMock();

        $this->platform = new Platform(
            $this->sdkFactoryMock,
            $this->configPlatformMock,
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
            'logging_level' => 'notice',
            'logging_file_name' => 'file_name'
        ];

        $websiteMock = $this->getMockBuilder(WebsiteInterface::class)->getMock();
        $websiteMock->expects($this->exactly(2))->method('getCode')->willReturn($websiteCode);

        $sdkMock = $this->getMockBuilder('SubscribePro\Sdk')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configPlatformMock->expects($this->once())
            ->method('getClientId')
            ->with($websiteCode)
            ->willReturn('123');
        $this->configPlatformMock->expects($this->once())
            ->method('getClientSecret')
            ->with($websiteCode)
            ->willReturn('clientSecret');
        $this->configPlatformMock->expects($this->once())
            ->method('isLogEnabled')
            ->with($websiteCode)
            ->willReturn(true);
        $this->configPlatformMock->expects($this->once())
            ->method('getLogLevel')
            ->with($websiteCode)
            ->willReturn('notice');
        $this->configPlatformMock->expects($this->once())
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
        
        $this->assertSame($sdkMock, $this->platform->getSdk($websiteId));
        $this->assertSame($sdkMock, $this->platform->getSdk($websiteId));
    }
}
