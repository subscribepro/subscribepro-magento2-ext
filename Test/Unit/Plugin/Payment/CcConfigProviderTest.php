<?php

namespace Swarming\SubscribePro\Test\Unit\Plugin\Payment;

use Magento\Framework\View\Asset\File;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use Swarming\SubscribePro\Plugin\Payment\CcConfigProvider;

class CcConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Payment\Model\CcConfig
     */
    protected $ccConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\Asset\Source
     */
    protected $assetSourceMock;

    /**
     * @var \Swarming\SubscribePro\Plugin\Payment\CcConfigProvider
     */
    protected $plugin;

    protected function setUp(): void
    {
        $this->ccConfigMock = $this->getMockBuilder(CcConfig::class)->disableOriginalConstructor()->getMock();
        $this->assetSourceMock = $this->getMockBuilder(Source::class)->disableOriginalConstructor()->getMock();

        $this->plugin = new CcConfigProvider($this->ccConfigMock, $this->assetSourceMock);
    }

    /**
     * @param string $code
     * @dataProvider shippedIconDataProvider
     */
    #[DataProvider('shippedIconDataProvider')]
    public function testAroundGetIconsReturnsIntegerSizeForSvgIcon($code)
    {
        $sourceFile = dirname(__DIR__, 4) . '/view/base/web/images/cc/' . $code . '.svg';
        $this->assertFileExists($sourceFile);

        $this->assertIconSize($code, $sourceFile);
    }

    public function testAroundGetIconsFallsBackWhenImageSizeIsUnreadable()
    {
        $sourceFile = tempnam(sys_get_temp_dir(), 'ccicon');
        file_put_contents($sourceFile, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        try {
            $this->assertFalse(getimagesize($sourceFile));
            $this->assertIconSize('vi', $sourceFile);
        } finally {
            unlink($sourceFile);
        }
    }

    /**
     * @return array
     */
    public static function shippedIconDataProvider()
    {
        return [['ae'], ['di'], ['mc'], ['vi']];
    }

    /**
     * @param string $code
     * @param string $sourceFile
     */
    private function assertIconSize($code, $sourceFile)
    {
        $assetMock = $this->createStub(File::class);
        $assetMock->method('getSourceFile')->willReturn($sourceFile);
        $assetMock->method('getUrl')->willReturn('https://example.com/' . $code . '.svg');

        $this->ccConfigMock->expects($this->once())->method('getCcAvailableTypes')
            ->willReturn([strtoupper($code) => 'Card']);
        $this->ccConfigMock->expects($this->once())->method('createAsset')
            ->with('Swarming_SubscribePro::images/cc/' . $code . '.svg')
            ->willReturn($assetMock);
        $this->assetSourceMock->expects($this->once())->method('findSource')->with($assetMock)
            ->willReturn($sourceFile);

        $icons = $this->plugin->aroundGetIcons(new \stdClass(), function () {
            return [];
        });

        $this->assertSame(39, $icons[strtoupper($code)]['width']);
        $this->assertSame(25, $icons[strtoupper($code)]['height']);
        $this->assertSame('https://example.com/' . $code . '.svg', $icons[strtoupper($code)]['url']);
    }
}
