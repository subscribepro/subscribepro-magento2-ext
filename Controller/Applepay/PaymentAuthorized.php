<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Controller\Applepay;

use Magento\Checkout\Model\DefaultConfigProvider as CheckoutDefaultConfigProvider;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Swarming\SubscribePro\Model\ApplePay\PaymentService;
use Swarming\SubscribePro\Model\ApplePay\Shipping as ApplePayShipping;

class PaymentAuthorized implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var PaymentService
     */
    private $paymentServie;
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
     * @var ApplePayShipping
     */
    private $shippingApplePay;

    /**
     * PaymentAuthorized constructor.
     *
     * @param RequestInterface              $request
     * @param PaymentService                $paymentService
     * @param ApplePayShipping              $shippingApplePay
     * @param JsonSerializer                $jsonSerializer
     * @param JsonResultFactory             $jsonResultFactory
     * @param CheckoutDefaultConfigProvider $defaultConfigProvider
     * @param UrlInterface                  $urlBuilder
     * @param LoggerInterface               $logger
     */
    public function __construct(
        RequestInterface $request,
        PaymentService $paymentService,
        ApplePayShipping $shippingApplePay,
        JsonSerializer $jsonSerializer,
        JsonResultFactory $jsonResultFactory,
        CheckoutDefaultConfigProvider $defaultConfigProvider,
        UrlInterface $urlBuilder,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->paymentServie = $paymentService;
        $this->jsonSerializer = $jsonSerializer;
        $this->resultJsonFactory = $jsonResultFactory;
        $this->logger = $logger;
        $this->defaultConfigProvider = $defaultConfigProvider;
        $this->urlBuilder = $urlBuilder;
        $this->shippingApplePay = $shippingApplePay;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setHeader('Content-type', 'application/json');
        $errorMessage = __('Apple Pay error. Please contact support for assistance.');

        try {
            // Get JSON POST
            $data = $this->getRequestData();

            if (!isset($data['payment'])) {
                throw new LocalizedException($errorMessage);
            }

            // Set shipping method selection
            $quoteId = $this->paymentServie->setPaymentToQuote($data['payment']);
            $shippingMethods = $this->shippingApplePay->getShippingMethods();
            $defaultShippingMethod = [];
            if (count($shippingMethods)) {
                $defaultShippingMethod = $shippingMethods[0];
            }

            $this->paymentServie->placeOrder($quoteId, $defaultShippingMethod);

            $redirectUrl = $this->defaultConfigProvider->getDefaultSuccessPageUrl();
            $urlToRedirect = $this->urlBuilder->getUrl('checkout/onepage/success/');

            $response = [
                'success' => true,
                'redirect' => $redirectUrl,
                'redirectUrl' => $urlToRedirect
            ];
            $result->setData($response);
            // phpcs:ignore Magento2.Exceptions.ThrowCatch.ThrowCatch
        } catch (LocalizedException $e) {
            if (isset($quoteId)) {
                $this->logger->error('QuoteId: ' . $quoteId);
            }
            $this->logger->error($e->getMessage());

            $response = [
                'success' => false,
                'is_exception' => true,
                'exception_message' => $e->getMessage(),
                'message' => (string) $errorMessage,
            ];
            $result->setData($response);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getRequestData(): array
    {
        return $this->jsonSerializer->unserialize($this->request->getContent());
    }

    /**
     * {@inheritdoc}
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $resultRedirect = $this->resultJsonFactory->create();
        $resultRedirect->setHttpResponseCode(401);

        return new InvalidRequestException(
            $resultRedirect,
            [__('Invalid Request.')]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $request->isPost();
    }
}
