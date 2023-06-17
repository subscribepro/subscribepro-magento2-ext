<?php

namespace Swarming\SubscribePro\Test\Unit\Controller\Cards;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\Controller\ResultFactory;
use Swarming\SubscribePro\Controller\Cards\NewAction;

class NewActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Controller\Cards\NewAction
     */
    protected $newActionController;

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

        $this->newActionController = new NewAction($contextMock);
    }

    public function testExecute()
    {
        $resultMock = $this->createResultForwardMock();
        $resultMock->expects($this->once())->method('forward')->with('edit')->willReturnSelf();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_FORWARD)
            ->willReturn($resultMock);

        $this->assertSame($resultMock, $this->newActionController->execute());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Controller\Result\Forward
     */
    private function createResultForwardMock()
    {
        return $this->getMockBuilder(ResultForward::class)->disableOriginalConstructor()->getMock();
    }
}
