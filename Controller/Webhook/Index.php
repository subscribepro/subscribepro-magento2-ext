<?php

namespace Swarming\SubscribePro\Controller\Webhook;

use Magento\Framework\App\Action\Action;

class Index extends Action
{
    /**
     * @var \Swarming\SubscribePro\Platform\Webhook\Processor
     */
    protected $webhookProcessor;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Webhook
     */
    protected $platformWebhookService;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Advanced
     */
    protected $advancedConfig;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Swarming\SubscribePro\Platform\Webhook\Processor $webhookProcessor
     * @param \Swarming\SubscribePro\Platform\Service\Webhook $platformWebhookService
     * @param \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Swarming\SubscribePro\Platform\Webhook\Processor $webhookProcessor,
        \Swarming\SubscribePro\Platform\Service\Webhook $platformWebhookService,
        \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->webhookProcessor = $webhookProcessor;
        $this->platformWebhookService = $platformWebhookService;
        $this->advancedConfig = $advancedConfig;
        $this->remoteAddress = $remoteAddress;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if (!$this->advancedConfig->isWebhookIpAllowed($this->remoteAddress->getRemoteAddress())) {
            return;
        }

        $event = $this->platformWebhookService->readEvent();
        try {
            if ($event) {
                $this->webhookProcessor->processEvent($event);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
