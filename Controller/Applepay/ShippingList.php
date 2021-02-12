<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Applepay;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Model\ApplePay\Shipping;

class ShippingList implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var JsonResultFactory
     */
    private $jsonResultFactory;
    /**
     * @var Shipping
     */
    private $shipping;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ShippingList constructor.
     *
     * @param RequestInterface $request
     * @param Shipping $shipping
     * @param JsonSerializer $jsonSerializer
     * @param JsonResultFactory $jsonResultFactory
     * @param LoggerInterface $logger
     */
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
        try {
            $data = $this->getRequestData();

            if (!isset($data['shippingContact'])) {
                throw new LocalizedException(new Phrase('Invalid Request Data!'));
            }

            // Pass over the shipping destination
            $this->shipping->setDataToQuote($data['shippingContact']);

            // Retrieve the shipping rates available for this quote
            $shippingMethodsForApplePay = $this->getShippingMethodsForApplePay();
            $grandTotalForApplePay = $this->getGrandTotal();
            $rowItemsApplePay = $this->getRowItems();
            $this->logger->debug('ShippingList::execute');
        } catch (LocalizedException $e) {
            $this->logger->debug('Error Message - ' . $e->getMessage());
            var_dump($e->getMessage());
            die;
        }

        // Build response
        $this->logger->debug('newShippingMethods - ' . print_r($shippingMethodsForApplePay, true));
        $this->logger->debug('newTotal - ' . print_r($grandTotalForApplePay, true));
        $this->logger->debug('newLineItems - ' . print_r($rowItemsApplePay, true));
        $response = [
            'newShippingMethods'    => $shippingMethodsForApplePay,
            'newTotal'              => $grandTotalForApplePay,
            'newLineItems'          => $rowItemsApplePay,
        ];

        // Return JSON response
        $result = $this->jsonResultFactory->create();
        $result->setHeader('Content-type', 'application/json');
        $result->setData($response);

        return $result;
    }

    /**
     * @return array|null
     */
    public function getRequestData()
    {
        return $this->jsonSerializer->unserialize($this->request->getContent());
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $resultRedirect = $this->jsonResultFactory->create();
        $resultRedirect->setHttpResponseCode(401);

        return new InvalidRequestException(
            $resultRedirect,
            [new Phrase('Invalid Post Request.')]
        );
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $request->isPost();
    }

    /**
     * @return array
     */
    public function getShippingMethodsForApplePay(): array
    {
        return $this->shipping->getShippingMethods();
    }

    /**
     * @return array
     */
    public function getGrandTotal(): array
    {
        return $this->shipping->getGrandTotal();
    }

    /**
     * @return array
     */
    public function getRowItems(): array
    {
        return $this->shipping->getRowItems();
    }
}
