<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Storage;

use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Swarming\SubscribePro\Platform\Storage\Config as ConfigStorage;
use Magento\Framework\Cache\FrontendInterface as CacheFrontendInterface;
use Magento\Framework\App\Cache\StateInterface as CacheStateInterface;
use Swarming\SubscribePro\Model\Config\Advanced as CacheConfig;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Storage\Config
     */
    protected $configStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Cache\FrontendInterface
     */
    protected $cacheMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Cache\StateInterface
     */
    protected $stateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\Advanced
     */
    protected $advancedConfigMock;

    protected function setUp()
    {
        $this->cacheMock = $this->getMockBuilder(CacheFrontendInterface::class)->getMock();
        $this->stateMock = $this->getMockBuilder(CacheStateInterface::class)->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->advancedConfigMock = $this->getMockBuilder(CacheConfig::class)
            ->disableOriginalConstructor()->getMock();

        $this->configStorage = new ConfigStorage(
            $this->cacheMock,
            $this->stateMock,
            $this->advancedConfigMock,
            $this->storeManagerMock
        );
    }

    public function testLoadIfConfigCacheDisabled()
    {
        $websiteId = 23;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Config::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->cacheMock->expects($this->never())->method('load');

        $this->assertNull($this->configStorage->load($websiteId));
    }

    public function testLoadIfConfigCacheNotLoaded()
    {
        $websiteId = 23;
        $websiteCode = 'web_code';
        $cacheKey = ConfigStorage::CONFIG_CACHE_KEY . '_' . $websiteCode;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->once())->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Config::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn(null);

        $this->assertNull($this->configStorage->load($websiteId));
    }

    public function testLoad()
    {
        $websiteId = 23;
        $websiteCode = 'web_code';
        $cacheKey = ConfigStorage::CONFIG_CACHE_KEY . '_' . $websiteCode;
        $configData = ['config'];

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Config::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->exactly(2))
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn(serialize($configData));

        $this->assertEquals(
            $configData,
            $this->configStorage->load($websiteId),
            'Fail to test config storage load from cache'
        );
        $this->assertEquals(
            $configData,
            $this->configStorage->load($websiteId),
            'Fail to test config storage load from internal cache'
        );
    }

    public function testSaveIfConfigCacheDisabled()
    {
        $websiteId = 23;
        $configData = ['config'];

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn('code');

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Config::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->cacheMock->expects($this->never())->method('save');

        $this->configStorage->save($configData, $websiteId);
    }

    public function testSaveWithoutLifeTime()
    {
        $websiteId = 2020;
        $lifeTime = 1010;
        $websiteCode = 'my_code';
        $cacheKey = ConfigStorage::CONFIG_CACHE_KEY . '_' . $websiteCode;
        $config = ['key' => 'val'];

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Config::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->advancedConfigMock->expects($this->once())
            ->method('getCacheLifeTime')
            ->with($websiteId)
            ->willReturn($lifeTime);

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with(
                serialize($config),
                $cacheKey,
                [],
                $lifeTime
            );

        $this->configStorage->save($config, $websiteId);
    }

    public function testSaveWithLifeTime()
    {
        $websiteId = 2020;
        $lifeTime = 1010;
        $websiteCode = 'my_code';
        $cacheKey = ConfigStorage::CONFIG_CACHE_KEY . '_' . $websiteCode;
        $config = ['key' => 'val'];

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects($this->any())->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects($this->once())
            ->method('isEnabled')
            ->with(\Swarming\SubscribePro\Platform\Cache\Type\Config::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->advancedConfigMock->expects($this->never())->method('getCacheLifeTime');

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with(
                serialize($config),
                $cacheKey,
                [],
                $lifeTime
            );

        $this->configStorage->save($config, $websiteId, $lifeTime);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Api\Data\WebsiteInterface
     */
    private function createWebsiteMock()
    {
        return $this->getMockBuilder(WebsiteInterface::class)->getMock();
    }
}
