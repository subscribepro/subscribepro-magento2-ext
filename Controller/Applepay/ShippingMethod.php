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

class ShippingMethod  implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;
    /**
     * @var Shipping
     */
    private Shipping $shipping;
    /**
     * @var JsonSerializer
     */
    private JsonSerializer $jsonSerializer;
    /**
     * @var JsonResultFactory
     */
    private JsonResultFactory $jsonResultFactory;

    public function __construct(
        RequestInterface $request,
        Shipping $shipping,
        JsonSerializer $jsonSerializer,
        JsonResultFactory $jsonResultFactory
    ) {
        $this->request = $request;
        $this->shipping = $shipping;
        $this->jsonSerializer = $jsonSerializer;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    public function execute()
    {
        try {
            // Get JSON POST
            $data = $this->getRequestData();

            if (!isset($data['shippingMethod'])) {
                throw new LocalizedException(new Phrase('Invalid Request Data!'));
            }

            // Set shipping method selection
            $this->shipping->setShippingMethodToQuote($data['shippingMethod']);

            // Build up our response
            $response = [
                'newTotal' => $this->getGrandTotal(),
                'newLineItems' => $this->getRowItems(),
            ];

            // Return JSON response
            $result = $this->jsonResultFactory->create();
            $result->setHeader('Content-type', 'application/json');
            $result->setData($this->jsonSerializer->serialize($response));

            return $result;

        } catch (LocalizedException $e) {
            var_dump($e->getMessage());
            die;
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
