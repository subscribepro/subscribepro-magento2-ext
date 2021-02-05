<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Applepay;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Swarming\SubscribePro\Model\ApplePay\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\DefaultConfigProvider as CheckoutDefaultConfigProvider;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class PaymentAuthorized implements HttpPostActionInterface, CsrfAwareActionInterface
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
    private $resultJsonFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var CheckoutDefaultConfigProvider
     */
    private $defaultConfigProvider;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

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
        CheckoutDefaultConfigProvider $defaultConfigProvider,
        UrlInterface $urlBuilder,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->payment = $payment;
        $this->jsonSerializer = $jsonSerializer;
        $this->resultJsonFactory = $jsonResultFactory;
        $this->logger = $logger;
        $this->defaultConfigProvider = $defaultConfigProvider;
        $this->urlBuilder = $urlBuilder;
    }

    public function execute()
    {
        $result = new DataObject();

        try {
            // Get JSON POST
            $data = $this->getRequestData();

            if (!isset($data['payment'])) {
                throw new LocalizedException(new Phrase('Invalid Request Data!'));
            }

            // Set shipping method selection
            $quoteId = $this->payment->setPaymentToQuote($data['payment']);
            $this->payment->placeOrder($quoteId);

            $redirectUrl = $this->defaultConfigProvider->getDefaultSuccessPageUrl();
            $urlToRedirect = $this->urlBuilder->getUrl('checkout/onepage/success/');
            $result->setData('redirect', $redirectUrl);
            $result->setData('redirectUrl', $urlToRedirect);

        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            $result->setData('success', false);
            $result->setData('error', true);
            $result->setData(
                'error_messages',
                new Phrase('Something went wrong while processing your order. Please try again later.')
            );
        }

        return $this->resultJsonFactory->create()->setData($result->getData());
    }

    public function getRequestData()
    {
        return $this->jsonSerializer->unserialize($this->request->getContent());
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $resultRedirect = $this->resultJsonFactory->create();
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
