<?php

namespace Swarming\SubscribePro\Test\Unit\Platform\Storage;

use Magento\Framework\App\Cache\StateInterface as CacheStateInterface;
use Magento\Framework\Cache\FrontendInterface as CacheFrontendInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swarming\SubscribePro\Model\Config\Advanced as CacheConfig;
use Swarming\SubscribePro\Platform\Cache\Type\Config;
use Swarming\SubscribePro\Platform\Storage\Config as ConfigStorage;

class ConfigTest extends TestCase
{
    /**
     * @var \Swarming\SubscribePro\Platform\Storage\Config
     */
    protected $configStorage;

    /**
     * @var \Magento\Framework\Cache\FrontendInterface|MockObject
     */
    protected $cacheMock;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface|MockObject
     */
    protected $stateMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Advanced|MockObject
     */
    protected $advancedConfigMock;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|MockObject
     */
    protected $serializerMock;

    protected function setUp(): void
    {
        $this->cacheMock = $this->getMockBuilder(CacheFrontendInterface::class)->getMock();
        $this->stateMock = $this->getMockBuilder(CacheStateInterface::class)->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->advancedConfigMock = $this->getMockBuilder(CacheConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMock();

        $this->configStorage = new ConfigStorage(
            $this->cacheMock,
            $this->stateMock,
            $this->advancedConfigMock,
            $this->storeManagerMock,
            $this->serializerMock
        );
    }

    public function testLoadIfConfigCacheDisabled(): void
    {
        $websiteId = 23;
        $websiteCode = 'website_23';

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')->willReturn($websiteCode);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Config::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->cacheMock->expects(self::never())->method('load');

        $this->serializerMock->expects(self::never())->method('unserialize');

        self::assertNull($this->configStorage->load($websiteId));
    }

    public function testLoadIfConfigCacheNotLoaded(): void
    {
        $websiteId = 23;
        $websiteCode = 'web_code';
        $cacheKey = ConfigStorage::CONFIG_CACHE_KEY . '_' . $websiteCode;

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->expects(self::once())
            ->method('getCode')
            ->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Config::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheMock->expects(self::once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn(null);

        $this->serializerMock->expects(self::never())->method('unserialize');

        self::assertNull($this->configStorage->load($websiteId));
    }

    public function testLoad(): void
    {
        $websiteId = 23;
        $websiteCode = 'web_code';
        $cacheKey = ConfigStorage::CONFIG_CACHE_KEY . '_' . $websiteCode;
        $configData = ['config'];
        $configDataSerialized = json_encode($configData);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Config::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::exactly(2))
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->cacheMock->expects(self::once())
            ->method('load')
            ->with($cacheKey)
            ->willReturn($configDataSerialized);

        $this->serializerMock->expects(self::once())
            ->method('unserialize')
            ->with($configDataSerialized)
            ->willReturn($configData);

        self::assertEquals(
            $configData,
            $this->configStorage->load($websiteId),
            'Fail to test config storage load from cache'
        );
        self::assertEquals(
            $configData,
            $this->configStorage->load($websiteId),
            'Fail to test config storage load from internal cache'
        );
    }

    public function testSaveIfConfigCacheDisabled(): void
    {
        $websiteId = 23;
        $configData = ['config'];

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')->willReturn('code');

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Config::TYPE_IDENTIFIER)
            ->willReturn(false);

        $this->serializerMock->expects(self::never())->method('serialize');

        $this->cacheMock->expects(self::never())->method('save');

        $this->configStorage->save($configData, $websiteId);
    }

    public function testSaveWithoutLifeTime(): void
    {
        $websiteId = 2020;
        $lifeTime = 1010;
        $websiteCode = 'my_code';
        $cacheKey = ConfigStorage::CONFIG_CACHE_KEY . '_' . $websiteCode;
        $config = ['key' => 'val'];
        $configSerialized = json_encode($config);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Config::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->advancedConfigMock->expects(self::once())
            ->method('getCacheLifeTime')
            ->with($websiteId)
            ->willReturn($lifeTime);

        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->with($config)
            ->willReturn($configSerialized);

        $this->cacheMock->expects(self::once())
            ->method('save')
            ->with(
                $configSerialized,
                $cacheKey,
                [],
                $lifeTime
            );

        $this->configStorage->save($config, $websiteId);
    }

    public function testSaveWithLifeTime(): void
    {
        $websiteId = 2121;
        $lifeTime = 1212;
        $websiteCode = 'website_code';
        $cacheKey = ConfigStorage::CONFIG_CACHE_KEY . '_' . $websiteCode;
        $config = ['key1' => 'val1', 'key2' => 'val2'];
        $configSerialized = json_encode($config);

        $websiteMock = $this->createWebsiteMock();
        $websiteMock->method('getCode')->willReturn($websiteCode);

        $this->stateMock->expects(self::once())
            ->method('isEnabled')
            ->with(Config::TYPE_IDENTIFIER)
            ->willReturn(true);

        $this->storeManagerMock->expects(self::once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($websiteMock);

        $this->advancedConfigMock->expects(self::never())->method('getCacheLifeTime');

        $this->serializerMock->expects(self::once())
            ->method('serialize')
            ->with($config)
            ->willReturn($configSerialized);

        $this->cacheMock->expects(self::once())
            ->method('save')
            ->with(
                $configSerialized,
                $cacheKey,
                [],
                $lifeTime
            );

        $this->configStorage->save($config, $websiteId, $lifeTime);
    }

    /**
     * @return \Magento\Store\Api\Data\WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createWebsiteMock()
    {
        return $this->getMockBuilder(WebsiteInterface::class)->getMock();
    }
}
