<?php

namespace Swarming\SubscribePro\Test\Unit\Controller\Webhook;

use Magento\Backend\App\Action\Context;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;
use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Controller\Webhook\Index;
use Swarming\SubscribePro\Platform\Webhook\Processor as WebhookProcessor;
use Swarming\SubscribePro\Model\Config\Advanced as AdvancedConfig;
use Swarming\SubscribePro\Platform\Service\Webhook as WebhookService;

class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Swarming\SubscribePro\Controller\Webhook\Index
     */
    protected $indexController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Webhook\Processor
     */
    protected $webhookProcessorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Platform\Service\Webhook
     */
    protected $platformWebhookServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swarming\SubscribePro\Model\Config\Advanced
     */
    protected $advancedConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddressMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    protected function setUp(): void
    {
        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->webhookProcessorMock = $this->getMockBuilder(WebhookProcessor::class)
            ->disableOriginalConstructor()->getMock();
        $this->platformWebhookServiceMock = $this->getMockBuilder(WebhookService::class)
            ->disableOriginalConstructor()->getMock();
        $this->advancedConfigMock = $this->getMockBuilder(AdvancedConfig::class)
            ->disableOriginalConstructor()->getMock();
        $this->remoteAddressMock = $this->getMockBuilder(RemoteAddress::class)
            ->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->indexController = new Index(
            $contextMock,
            $this->webhookProcessorMock,
            $this->platformWebhookServiceMock,
            $this->advancedConfigMock,
            $this->remoteAddressMock,
            $this->loggerMock
        );
    }

    public function testExecuteWebhookIdIsNotAllowed()
    {
        $ip = '10.10.10.231';

        $this->remoteAddressMock->expects($this->once())
            ->method('getRemoteAddress')
            ->willReturn($ip);

        $this->advancedConfigMock->expects($this->once())
            ->method('isWebhookIpAllowed')
            ->with($ip)
            ->willReturn(false);

        $this->platformWebhookServiceMock->expects($this->never())->method('readEvent');
        $this->webhookProcessorMock->expects($this->never())->method('processEvent');

        $this->indexController->execute();
    }

    public function testExecuteIfNoEvent()
    {
        $ip = '10.10.15.232';

        $this->remoteAddressMock->expects($this->once())
            ->method('getRemoteAddress')
            ->willReturn($ip);

        $this->advancedConfigMock->expects($this->once())
            ->method('isWebhookIpAllowed')
            ->with($ip)
            ->willReturn(true);

        $this->platformWebhookServiceMock->expects($this->once())
            ->method('readEvent')
            ->willReturn(false);

        $this->webhookProcessorMock->expects($this->never())->method('processEvent');

        $this->indexController->execute();
    }

    public function testExecuteIfFailToProcessEvent()
    {
        $exception = new \InvalidArgumentException('error');

        $ip = '10.10.15.232';
        $webhookEvent = $this->createWebhookEventMock();

        $this->remoteAddressMock->expects($this->once())
            ->method('getRemoteAddress')
            ->willReturn($ip);

        $this->advancedConfigMock->expects($this->once())
            ->method('isWebhookIpAllowed')
            ->with($ip)
            ->willReturn(true);

        $this->platformWebhookServiceMock->expects($this->once())
            ->method('readEvent')
            ->willReturn($webhookEvent);

        $this->webhookProcessorMock->expects($this->once())
            ->method('processEvent')
            ->with($webhookEvent)
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->indexController->execute();
    }

    public function testExecute()
    {
        $ip = '10.10.15.232';
        $webhookEvent = $this->createWebhookEventMock();

        $this->remoteAddressMock->expects($this->once())
            ->method('getRemoteAddress')
            ->willReturn($ip);

        $this->advancedConfigMock->expects($this->once())
            ->method('isWebhookIpAllowed')
            ->with($ip)
            ->willReturn(true);

        $this->platformWebhookServiceMock->expects($this->once())
            ->method('readEvent')
            ->willReturn($webhookEvent);

        $this->webhookProcessorMock->expects($this->once())
            ->method('processEvent')
            ->with($webhookEvent);

        $this->loggerMock->expects($this->never())->method('critical');

        $this->indexController->execute();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\SubscribePro\Service\Webhook\EventInterface
     */
    private function createWebhookEventMock()
    {
        return $this->getMockBuilder(EventInterface::class)->getMock();
    }
}
