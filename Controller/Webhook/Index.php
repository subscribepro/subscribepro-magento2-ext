<?php

namespace Swarming\SubscribePro\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Response;

class Index extends Action implements CsrfAwareActionInterface
{
    const WEBHOOK_HASH_HEADER_KEY = 'Sp-Hmac';

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
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Swarming\SubscribePro\Platform\Webhook\Processor $webhookProcessor
     * @param \Swarming\SubscribePro\Platform\Service\Webhook $platformWebhookService
     * @param \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Swarming\SubscribePro\Platform\Webhook\Processor $webhookProcessor,
        \Swarming\SubscribePro\Platform\Service\Webhook $platformWebhookService,
        \Swarming\SubscribePro\Model\Config\Advanced $advancedConfig,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->webhookProcessor = $webhookProcessor;
        $this->platformWebhookService = $platformWebhookService;
        $this->advancedConfig = $advancedConfig;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->request = $request;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        // Check hash from header
        // Get secret key from config
        if (!$this->validateWebhookHash()) {
            $result->setHttpResponseCode(Exception::HTTP_FORBIDDEN);
            $result->setData(
                ['error_message' => __('Could not validate webhook source using hash. Check secret key.')]
            );
            return $result;
        }

        // Now read the event
        $event = $this->platformWebhookService->readEvent();
        if (!$event) {
            $result->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $result->setData(['error_message' => __('Webhook data is malformed or did not contain a valid event')]);
            return $result;
        }

        try {
            $this->webhookProcessor->processEvent($event);
        } catch (NoSuchEntityException $e) {
            $result->setHttpResponseCode(Exception::HTTP_NOT_FOUND);
            $result->setData(['error_message' => $e->getMessage()]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $result->setHttpResponseCode(Exception::HTTP_INTERNAL_ERROR);
            $result->setData(['error_message' => $e->getMessage()]);
            return $result;
        }

        $result->setHttpResponseCode(Response::HTTP_OK);
        $result->setData(null);
        return $result;
    }

    /**
     * Validate the hash in the webhook request header against the hash generated from the received webhook content
     *
     * @return bool
     */
    protected function validateWebhookHash()
    {
        $secretKey = $this->advancedConfig->getWebhookSecretKey();

        $hashFromRequest = $this->request->getHeader(self::WEBHOOK_HASH_HEADER_KEY);
        $body = $this->request->getContent();

        if (!$hashFromRequest) {
            return false;
        }

        $hash = hash_hmac('sha256', $body, $secretKey);
        return hash_equals($hash, $hashFromRequest);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
