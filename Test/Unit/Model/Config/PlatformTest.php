<?php

namespace Swarming\SubscribePro\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Swarming\SubscribePro\Model\Config\Platform;
use Magento\Store\Model\ScopeInterface;

class PlatformTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Platform
     */
    protected $platformConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platformConfig = new Platform($this->scopeConfigMock, $this->directoryList);
    }

    public function testGetBaseUrl()
    {
        $websiteCode = 'website_code';
        $url = 'url';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/platform/base_url', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($url);

        $this->assertEquals($url, $this->platformConfig->getBaseUrl($websiteCode));
    }

    public function testGetClientId()
    {
        $websiteCode = 'website_code';
        $clientId = '111ddd';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/platform/client_id', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($clientId);

        $this->assertEquals($clientId, $this->platformConfig->getClientId($websiteCode));
    }

    /**
     * @param mixed $websiteCode
     * @param string $scopeType
     * @param string $clientSecret
     * @dataProvider getClientSecretDataProvider
     */
    public function testGetClientSecret($websiteCode, $scopeType, $clientSecret)
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/platform/client_secret', $scopeType, $websiteCode)
            ->willReturn($clientSecret);

        $this->assertEquals($clientSecret, $this->platformConfig->getClientSecret($websiteCode));
    }

    /**
     * @return array
     */
    public function getClientSecretDataProvider()
    {
        return [
            'Website code is false' => [
                'websiteCode' => false,
                'scopeType' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                'clientSecret' => '123fdsg'
            ],
            'Website code is empty' => [
                'websiteCode' => null,
                'scopeType' => ScopeInterface::SCOPE_WEBSITE,
                'clientSecret' => '4334sdfdsf'
            ],
            'With website code' => [
                'websiteCode' => 'main_website',
                'scopeType' => ScopeInterface::SCOPE_WEBSITE,
                'clientSecret' => '23123dfsdf'
            ]
        ];
    }

    public function testIsLogEnabled()
    {
        $websiteCode = 'website_code';
        $isLogEnabled = true;

        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('swarming_subscribepro/platform/log_enabled', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($isLogEnabled);

        $this->assertEquals($isLogEnabled, $this->platformConfig->isLogEnabled($websiteCode));
    }

    public function testLogFilename()
    {
        $websiteCode = 'custom_website';
        $varDirPath = implode(DIRECTORY_SEPARATOR, ['var', 'dir', 'path']);
        $fileName = DIRECTORY_SEPARATOR . 'filename';
        $result = implode(DIRECTORY_SEPARATOR, ['var', 'dir', 'path', 'filename']);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('swarming_subscribepro/platform/log_filename', ScopeInterface::SCOPE_WEBSITE, $websiteCode)
            ->willReturn($fileName);

        $this->directoryList->expects($this->once())
            ->method('getPath')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($varDirPath);

        $this->assertEquals($result, $this->platformConfig->getLogFilename($websiteCode));
    }
}
