<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Swarming\SubscribePro\Model\Config\General;
use Magento\Store\Model\ScopeInterface;

class GeneralTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->generalConfig = new General($this->scopeConfigMock);
    }

    public function testIsEnabled()
    {
        $websiteCode = 'code';
        $isEnabled = false;

        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('swarming_subscribepro/general/enabled', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($isEnabled);

        $this->assertEquals($isEnabled, $this->generalConfig->isEnabled($websiteCode));
    }
}
