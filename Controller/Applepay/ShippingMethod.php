<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Applepay;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Swarming\SubscribePro\Model\ApplePay\Shipping;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ShippingMethod implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Shipping
     */
    private $shipping;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var JsonResultFactory
     */
    private $jsonResultFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RequestInterface $request,
        Shipping $shipping,
        JsonSerializer $jsonSerializer,
        JsonResultFactory $jsonResultFactory,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->shipping = $shipping;
        $this->jsonSerializer = $jsonSerializer;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        // Return JSON response
        $result = $this->jsonResultFactory->create();
        $result->setHeader('Content-type', 'application/json');
        $errorMessage = new Phrase('Shipping method error. Please select a different shipping method.');

        try {
            // Get JSON POST
            $data = $this->getRequestData();

            if (!isset($data['shippingMethod'])) {
                throw new LocalizedException($errorMessage);
            }

            // Set shipping method selection
            $this->shipping->setShippingMethodToQuote($data['shippingMethod']);

            // Build up our response
            $response = [
                'success' => true,
                'newTotal' => $this->getGrandTotal(),
                'newLineItems' => $this->getRowItems(),
            ];

            $result->setData($response);

            return $result;
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            $response = [
                'success' => false,
                'is_exception' => true,
                'exception_message' => $e->getMessage(),
                'errorCode' => '',
                'contactField' => '',
                'message' => (string) $errorMessage,
                'newTotal' => [
                    'label' => 'MERCHANT',
                    'amount' => 0
                ],
                'newLineItems' => []
            ];
            $result->setData($response);
        }

        return $result;
    }

    public function getRequestData()
    {
        return $this->jsonSerializer->unserialize($this->request->getContent());
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $resultRedirect = $this->jsonResultFactory->create();
        $resultRedirect->setHttpResponseCode(401);

        return new InvalidRequestException(
            $resultRedirect,
            [new Phrase('Invalid Post Request.')]
        );
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $request->isPost();
    }

    public function getRowItems()
    {
        return $this->shipping->getRowItems();
    }

    public function getGrandTotal()
    {
        return $this->shipping->getGrandTotal();
    }
}
