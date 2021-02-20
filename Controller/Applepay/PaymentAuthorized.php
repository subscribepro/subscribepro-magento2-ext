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
            $quoteId = $this->paymentServie->setPaymentToQuote($data['payment']);
            $shippingMethods = $this->shippingApplePay->getShippingMethods();
            $defaultShippingMethod = [];
            if (count($shippingMethods)) {
                $defaultShippingMethod = $shippingMethods[0];
            }

            $this->paymentServie->placeOrder($quoteId, $defaultShippingMethod);

            $redirectUrl = $this->defaultConfigProvider->getDefaultSuccessPageUrl();
            $urlToRedirect = $this->urlBuilder->getUrl('checkout/onepage/success/');
            $result->setData('redirect', $redirectUrl);
            $this->logger->debug('redirectUrl - ' . $urlToRedirect);
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
