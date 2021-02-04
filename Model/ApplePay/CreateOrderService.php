<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\CatalogInventory\Helper\Data as CatalogInventoryData;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\BillingAddressManagement;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\ShippingAddressManagement;

class CreateOrderService
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
     * @var Response
     */
    private $response;
    /**
     * @var PaymentLogger
     */
    private $paymentLogger;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteManagement $quoteManagement,
        AddressFactory $addressFactory,
        ShippingAddressManagement $shippingAddressManagement,
        BillingAddressManagement $billingAddressManagement,
        Response $response,
        PaymentLogger $paymentLogger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->addressFactory = $addressFactory;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentLogger = $paymentLogger;
    }

    public function createOrder($quoteId): string
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        if (!$quote || !$quote->getIsActive()) {
            throw  new LocalizedException(__('Something going wrong with display_id'));
        }

        $shippingAddress = $quote->getShippingAddress();

        var_dump($shippingAddress->getShippingMethod());
        var_dump($shippingAddress->getShippingMethod());
        die;
        $quote->collectTotals();

        $this->quoteRepository->save($quote);

        $quoteId = $quote->getId();
        $storeId = $quote->getStoreId();

        $order = $this->quoteManagement->submit($quote);

        $incrementId = $order->getIncrementId() ?? null;

        var_dump($incrementId);
        die;

    }
}
