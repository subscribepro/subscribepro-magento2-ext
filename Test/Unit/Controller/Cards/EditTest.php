<?php

namespace Swarming\SubscribePro\Test\Unit\Controller\Cards;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Element\Html\Links as NavigationBlock;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title as PageTitle;
use Magento\Framework\View\Result\Page as ResultPage;
use Swarming\SubscribePro\Controller\Cards\Edit;
use Swarming\SubscribePro\Gateway\Config\VaultConfig;

class EditTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Controller\Cards\Edit
     */
    protected $editController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Gateway\Config\VaultConfig
     */
    protected $platformVaultConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactoryMock;

    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactoryMock);

        $this->platformVaultConfigMock = $this->getMockBuilder(VaultConfig::class)
            ->disableOriginalConstructor()->getMock();

        $this->editController = new Edit(
            $contextMock,
            $this->platformVaultConfigMock
        );
    }

    public function testExecuteIfVaultConfigNotActive()
    {
        $resultMock = $this->createResultForwardMock();
        $resultMock->expects($this->once())->method('forward')->with('noroute')->willReturnSelf();

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_FORWARD)
            ->willReturn($resultMock);

        $this->assertSame($resultMock, $this->editController->execute());
    }

    public function testExecuteIfNoNavigationBlock()
    {
        $pageTitleMock = $this->createPageTitleMock();
        $pageTitleMock->expects($this->once())->method('set')->with(__('Edit Credit Card'));

        $pageConfigMock = $this->createPageConfigMock();
        $pageConfigMock->expects($this->once())->method('getTitle')->willReturn($pageTitleMock);

        $layoutMock = $this->createLayoutMock();
        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('customer_account_navigation')
            ->willReturn(null);

        $pageMock = $this->createResultPageMock();
        $pageMock->expects($this->once())->method('getConfig')->willReturn($pageConfigMock);
        $pageMock->expects($this->once())->method('getLayout')->willReturn($layoutMock);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($pageMock);

        $this->assertSame($pageMock, $this->editController->execute());
    }

    public function testExecuteWithNavigationBlock()
    {
        $pageTitleMock = $this->createPageTitleMock();
        $pageTitleMock->expects($this->once())->method('set')->with(__('Edit Credit Card'));

        $pageConfigMock = $this->createPageConfigMock();
        $pageConfigMock->expects($this->once())->method('getTitle')->willReturn($pageTitleMock);

        $navigationBlockMock = $this->createNavigationBlockMock();
        $navigationBlockMock->expects($this->once())->method('setActive')->with('vault/cards/listaction');

        $layoutMock = $this->createLayoutMock();
        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('customer_account_navigation')
            ->willReturn($navigationBlockMock);

        $pageMock = $this->createResultPageMock();
        $pageMock->expects($this->once())->method('getConfig')->willReturn($pageConfigMock);
        $pageMock->expects($this->once())->method('getLayout')->willReturn($layoutMock);

        $this->platformVaultConfigMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($pageMock);

        $this->assertSame($pageMock, $this->editController->execute());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\Result\Forward
     */
    private function createResultForwardMock()
    {
        return $this->getMockBuilder(ResultForward::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\Result\Page
     */
    private function createResultPageMock()
    {
        return $this->getMockBuilder(ResultPage::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\Page\Config
     */
    private function createPageConfigMock()
    {
        return $this->getMockBuilder(PageConfig::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\Page\Title
     */
    private function createPageTitleMock()
    {
        return $this->getMockBuilder(PageTitle::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\Layout
     */
    private function createLayoutMock()
    {
        return $this->getMockBuilder(Layout::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\View\Element\Html\Links
     */
    private function createNavigationBlockMock()
    {
        return $this->getMockBuilder(NavigationBlock::class)->disableOriginalConstructor()->getMock();
    }
}
