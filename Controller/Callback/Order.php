<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Callback;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\ResultFactory;

class Order implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const HMAC_SIGNATURE_HEADER = 'Sp-Hmac';

    private const HTTP_STATUS_SUCCESS = 201;
    private const HTTP_STATUS_PARTIAL_SUCCESS = 202;
    private const HTTP_STATUS_FAIL = 409;

    /**
     * @var \Magento\Framework\App\RequestInterface|\Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    private $resultFactory;

    /**
     * @var \Swarming\SubscribePro\Service\OrderCallback\PlaceOrder
     */
    private $placeOrderService;

    /**
     * @var \Swarming\SubscribePro\Model\Config\OrderCallback
     */
    private $orderCallbackConfig;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Swarming\SubscribePro\Service\OrderCallback\PlaceOrder $placeOrderService
     * @param \Swarming\SubscribePro\Model\Config\OrderCallback $orderCallbackConfig
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Swarming\SubscribePro\Service\OrderCallback\PlaceOrder $placeOrderService,
        \Swarming\SubscribePro\Model\Config\OrderCallback $orderCallbackConfig,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->placeOrderService = $placeOrderService;
        $this->orderCallbackConfig = $orderCallbackConfig;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): ResultInterface
    {
        $orderRequest = $this->serializer->unserialize($this->request->getContent());

        try {
            $responseData = $this->placeOrderService->execute($orderRequest);
            $responseCode = $this->getResponseCode($responseData);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $responseData = ['errorMessage' => (string)$e->getMessage()];
            $responseCode = self::HTTP_STATUS_FAIL;
        }

        if ($this->orderCallbackConfig->isLogEnabled()) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $this->logger->debug(print_r(['orderRequest' => $orderRequest, 'response' => $responseData], true));
        }

        /** @var \Magento\Framework\Controller\Result\Json $jsonResult */
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setHttpResponseCode($responseCode);
        $jsonResult->setData($responseData);
        return $jsonResult;
    }

    /**
     * @param array $responseData
     * @return int
     */
    private function getResponseCode(array $responseData): int
    {
        if (!empty($responseData['orderNumber']) && empty($responseData['errorItems'])) {
            $responseCode = self::HTTP_STATUS_SUCCESS;
        } elseif (!empty($responseData['orderNumber'])) {
            $responseCode = self::HTTP_STATUS_PARTIAL_SUCCESS;
        } else {
            $responseCode = self::HTTP_STATUS_FAIL;
        }
        return $responseCode;
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\Request\InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        /** @var \Magento\Framework\Controller\Result\Json $jsonResult */
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setHttpResponseCode(self::HTTP_STATUS_FAIL);
        $jsonResult->setData(
            [
                'errorMessage' => 'Invalid order callback configuration. Invalid callback shared secret.',
                'errorClass' => 'Technology'
            ]
        );

        return new InvalidRequestException($jsonResult);
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $hmacSignature = (string)$request->getHeader(self::HMAC_SIGNATURE_HEADER);

        $sharedSecret = $this->orderCallbackConfig->getSharedSecret();
        $body = $request->getContent();
        $calculatedHash = hash_hmac('sha256', $body, $sharedSecret, false);

        return hash_equals($calculatedHash, $hmacSignature);
    }
}
