<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Checkout\Helper\Data as CheckoutHelperData;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\QuoteManagement;
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
     * @var CheckoutHelperData
     */
    private $checkoutHelperData;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OrderService constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteManagement $quoteManagement
     * @param CheckoutHelperData $checkoutHelperData
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteManagement $quoteManagement,
        CheckoutHelperData $checkoutHelperData,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->checkoutHelperData = $checkoutHelperData;
        $this->logger = $logger;
    }

    /**
     * @param int $quoteId
     * @param array|null $defaultShippingMethod
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createOrder($quoteId, $defaultShippingMethod = null): bool
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        if (!$quote || !$quote->getIsActive()) {
            $this->logger->error('QuoteID: ' . $quoteId);
            $this->logger->error('Quote is not active or null');
            throw  new LocalizedException(__('Something going wrong.'));
        }

        /** @var QuoteAddress $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress->getShippingMethod()) {
            /*
             * In case when only one shipping_method available the apple pay does not trigger an event
             * with "onshippingmethodselected".
             */
            if (!$defaultShippingMethod) {
                $errMsg = 'Cannot find shipping method. Please check your shipping method list';
                $this->logger->error('QuoteId: ' . $quoteId);
                $this->logger->error($errMsg);
                throw new LocalizedException(__($errMsg));
            }
            $shippingAddress->setShippingMethod($defaultShippingMethod['identifier']);
        }

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        try {
            $paymentMethod = $quote->getPayment();
            $this->quoteManagement->placeOrder($quote->getId(), $paymentMethod);

            return true;
        } catch (LocalizedException $e) {
            $this->logger->error('QuoteId: ' . $quote->getId());
            $this->logger->error($e->getMessage());
            $this->checkoutHelperData->sendPaymentFailedEmail(
                $quote,
                $e->getMessage()
            );

            throw $e;
        }
    }
}
