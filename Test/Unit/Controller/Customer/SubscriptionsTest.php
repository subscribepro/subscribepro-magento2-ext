<?php

namespace Swarming\SubscribePro\Test\Unit\Controller\Cards;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Swarming\SubscribePro\Controller\Customer\Subscriptions;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title as PageTitle;
use Swarming\SubscribePro\Model\Config\General as GeneralConfig;

class SubscriptionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Controller\Customer\Subscriptions
     */
    protected $editController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\General
     */
    protected $generalConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactoryMock;

    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)->disableOriginalConstructor()->getMock();

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->expects($this->once())->method('getResultFactory')->willReturn($this->resultFactoryMock);

        $this->generalConfigMock = $this->getMockBuilder(GeneralConfig::class)
            ->disableOriginalConstructor()->getMock();

        $this->editController = new Subscriptions(
            $contextMock,
            $this->generalConfigMock
        );
    }

    public function testExecuteIfSubscribeProNotEnabled()
    {
        $resultMock = $this->createResultForwardMock();
        $resultMock->expects($this->once())->method('forward')->with('noroute')->willReturnSelf();

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_FORWARD)
            ->willReturn($resultMock);

        $this->assertSame($resultMock, $this->editController->execute());
    }

    public function testExecute()
    {
        $pageTitleMock = $this->createPageTitleMock();
        $pageTitleMock->expects($this->once())->method('set')->with(__('My Product Subscriptions'));

        $pageConfigMock = $this->createPageConfigMock();
        $pageConfigMock->expects($this->once())->method('getTitle')->willReturn($pageTitleMock);

        $pageMock = $this->createResultPageMock();
        $pageMock->expects($this->once())->method('getConfig')->willReturn($pageConfigMock);

        $this->generalConfigMock->expects($this->once())
            ->method('isEnabled')
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
}
