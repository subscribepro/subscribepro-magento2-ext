<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\BillingAddressManagement;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\ShippingAddressManagement;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Helper\Data as CheckoutHelperData;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;

class OrderService
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;
    /**
     * @var AddressFactory
     */
    private $addressFactory;
    /**
     * @var ShippingAddressManagement
     */
    private $shippingAddressManagement;
    /**
     * @var BillingAddressManagement
     */
    private $billingAddressManagement;
    /**
     * @var PaymentLogger
     */
    private $paymentLogger;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var CheckoutHelperData
     */
    private $checkoutHelperData;
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteManagement $quoteManagement,
        CheckoutSession $checkoutSession,
        AddressFactory $addressFactory,
        ShippingAddressManagement $shippingAddressManagement,
        BillingAddressManagement $billingAddressManagement,
        PaymentLogger $paymentLogger,
        CheckoutHelperData $checkoutHelperData,
        OrderSender $orderSender,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->addressFactory = $addressFactory;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentLogger = $paymentLogger;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelperData = $checkoutHelperData;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
    }

    public function createOrder($quoteId, $defaultShippingMethod = null): bool
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        if (!$quote || !$quote->getIsActive()) {
            throw  new LocalizedException(__('Something going wrong with display_id'));
        }

        /** @var QuoteAddress $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress->getShippingMethod()) {
            /*
             * case when only one shipping_method available the apple pay does not trigger an event
             * with "onshippingmethodselected".
             */
            if (!$defaultShippingMethod) {
                throw new LocalizedException(__('Cannot find shipping method. Please check your shipping method list'));
            }
            var_dump($defaultShippingMethod);
            die;
            $shippingAddress->setShippingMethod($defaultShippingMethod['identifier']);
        }

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        try {
            /** @var Order $order */
            $order = $this->quoteManagement->submit($quote);

            // TODO: need to check redirect url if success page was changed by 3rd party module.
//            $redirectUrl = $quote->getPayment()->getOrderPlaceRedirectUrl();
//            if (!$redirectUrl) {
//                $redirectUrl = $this->defaultConfigProvider->getDefaultSuccessPageUrl();
//            }

            $this->checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            return true;
        } catch (LocalizedException $e) {
            $this->checkoutHelperData->sendPaymentFailedEmail(
                $quote,
                $e->getMessage()
            );

            throw $e;
        }
    }
}
