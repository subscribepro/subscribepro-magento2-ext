<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Swarming\SubscribePro\Model\Config\Advanced;
use Magento\Store\Model\ScopeInterface;

class AdvancedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Advanced
     */
    protected $advancedConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->advancedConfig = new Advanced($this->scopeConfigMock);
    }

    public function testGetCacheLifeTime()
    {
        $websiteCode = 'code';
        $lifeTime = 500;

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/advanced/cache_lifetime', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($lifeTime);

        $this->assertEquals($lifeTime, $this->advancedConfig->getCacheLifeTime($websiteCode));
    }
}
