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

    /**
     * @param string $ips
     * @param string $websiteCode
     * @param array $result
     * @dataProvider getWebhookIpAddressesDataProvider
     */
    public function testGetWebhookIpAddresses($ips, $websiteCode, $result)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/advanced/webhook_ipaddresses', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($ips);

        $this->assertEquals($result, $this->advancedConfig->getWebhookIpAddresses($websiteCode));
    }

    /**
     * @return array
     */
    public function getWebhookIpAddressesDataProvider()
    {
        return [
            'Empty ips' => [
                'ips' => '',
                'websiteCode' => 'code',
                'result' => []
            ],
            'Not empty ips' => [
                'ips' => 'ip1,ip2',
                'websiteCode' => 'main_website',
                'result' => ['ip1', 'ip2']
            ]
        ];
    }

    /**
     * @param string $ipAddress
     * @param string $ips
     * @param string $websiteCode
     * @param bool $result
     * @dataProvider isWebhookIpAllowedDataProvider
     */
    public function testIsWebhookIpAllowed($ipAddress, $ips, $websiteCode, $result)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/advanced/webhook_ipaddresses', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($ips);

        $this->assertEquals($result, $this->advancedConfig->isWebhookIpAllowed($ipAddress, $websiteCode));
    }

    /**
     * @return array
     */
    public function isWebhookIpAllowedDataProvider()
    {
        return [
            'Webhook ip not allowed' => [
                'ipAddress' => 'ip3',
                'ips' => 'ip1,ip2',
                'websiteCode' => 'main_website',
                'result' => false
            ],
            'Not empty ips' => [
                'ipAddress' => '127.0.0.0',
                'ips' => '127.0.0.0, 123.12.12.5',
                'websiteCode' => 'code',
                'result' => true
            ]
        ];
    }
}
