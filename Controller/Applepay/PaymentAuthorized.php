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
use Swarming\SubscribePro\Model\ApplePay\Payment;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class PaymentAuthorized  implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Payment
     */
    private $payment;
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

    /**
     * PaymentAuthorized constructor.
     *
     * @param RequestInterface  $request
     * @param Payment           $payment
     * @param JsonSerializer    $jsonSerializer
     * @param JsonResultFactory $jsonResultFactory
     * @param LoggerInterface   $logger
     */
    public function __construct(
        RequestInterface $request,
        Payment $payment,
        JsonSerializer $jsonSerializer,
        JsonResultFactory $jsonResultFactory,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->payment = $payment;
        $this->jsonSerializer = $jsonSerializer;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            // Get JSON POST
            $data = $this->getRequestData();

            if (!isset($data['payment'])) {
                throw new LocalizedException(new Phrase('Invalid Request Data!'));
            }

            // Set shipping method selection
            $quoteId = $this->payment->setPaymentToQuote($data['payment']);
            $this->payment->placeOrder($quoteId);

            // Build up our response
            $response = [
                'redirectUrl' => 'checkout/onepage/success',
            ];

            // Return JSON response
            $result = $this->jsonResultFactory->create();
            $result->setHeader('Content-type', 'application/json');
            $result->setData($this->jsonSerializer->serialize($response));

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
}
