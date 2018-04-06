<?php

namespace Swarming\SubscribePro\Model\Quote;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Quote\Model\QuoteValidator;
use Magento\Quote\Model\CustomerManagement;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Address\ToOrder as ToOrderConverter;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment as ToOrderPaymentConverter;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Sales\Api\OrderManagementInterface as OrderManagement;
use Magento\Sales\Test\Block\Adminhtml\Order\View\Tab\Info\PaymentInfoBlock;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class QuoteManagement
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class QuoteManagement extends \Magento\Quote\Model\QuoteManagement
{

    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    protected $subscriptionConfigGeneral;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    protected $subscriptionQuoteHelper;

    /**
     * @param EventManager $eventManager
     * @param QuoteValidator $quoteValidator
     * @param OrderFactory $orderFactory
     * @param OrderManagement $orderManagement
     * @param CustomerManagement $customerManagement
     * @param ToOrderConverter $quoteAddressToOrder
     * @param ToOrderAddressConverter $quoteAddressToOrderAddress
     * @param ToOrderItemConverter $quoteItemToOrderItem
     * @param ToOrderPaymentConverter $quotePaymentToOrderPayment
     * @param UserContextInterface $userContext
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerModelFactory
     * @param \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param QuoteFactory $quoteFactory
     * @param \Swarming\SubscribePro\Helper\Quote $subscriptionQuoteHelper
     * @param \Swarming\SubscribePro\Model\Config\General $subscriptionConfig
     * @param \Magento\Quote\Model\QuoteIdMaskFactory|null $quoteIdMaskFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface|null $addressRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EventManager $eventManager,
        QuoteValidator $quoteValidator,
        OrderFactory $orderFactory,
        OrderManagement $orderManagement,
        CustomerManagement $customerManagement,
        ToOrderConverter $quoteAddressToOrder,
        ToOrderAddressConverter $quoteAddressToOrderAddress,
        ToOrderItemConverter $quoteItemToOrderItem,
        ToOrderPaymentConverter $quotePaymentToOrderPayment,
        UserContextInterface $userContext,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerModelFactory,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Swarming\SubscribePro\Helper\Quote $subscriptionQuoteHelper,
        \Swarming\SubscribePro\Helper\QuoteItem $subscriptionQuoteItemHelper,
        \Swarming\SubscribePro\Model\Config\General $subscriptionConfigGeneral,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory = null,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository = null
    ) {
        $this->subscriptionQuoteHelper = $subscriptionQuoteHelper;
        $this->subscriptionQuoteItemHelper = $subscriptionQuoteItemHelper;
        $this->subscriptionConfigGeneral = $subscriptionConfigGeneral;

        // $quoteIdMaskFactory and $addressRepository are defined as private in the parent class
        // so instead of just defining all of the parameters here I have to pass them through to the constructor
        parent::__construct(
            $eventManager,
            $quoteValidator,
            $orderFactory,
            $orderManagement,
            $customerManagement,
            $quoteAddressToOrder,
            $quoteAddressToOrderAddress,
            $quoteItemToOrderItem,
            $quotePaymentToOrderPayment,
            $userContext,
            $quoteRepository,
            $customerRepository,
            $customerModelFactory,
            $quoteAddressFactory,
            $dataObjectHelper,
            $storeManager,
            $checkoutSession,
            $customerSession,
            $accountManagement,
            $quoteFactory,
            $quoteIdMaskFactory,
            $addressRepository
      );
    }

    /**
     * {@inheritdoc}
     */
    public function placeOrder($cartId, PaymentInterface $paymentMethod = null)
    {
        $quote = $this->quoteRepository->getActive($cartId);

        // Check config to see if extension functionality is enabled
        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->subscriptionConfigGeneral->isEnabled($websiteCode)) {
            return parent::placeOrder($cartId, $paymentMethod);
        }

        // Fire event indicating a subscription re-order
        $subscriptionItems = $this->subscriptionQuoteHelper->getSubscriptionItems($quote);
        if (!empty($subscriptionItems)) {
            $recurringOrder = false;
            foreach ($subscriptionItems as $subscriptionItem) {
                // Frontend initial order doesn't have a subscription_id on the quote item
                // If a quote includes a subscription_id before placeOrder(), it must be recurring
                if ($sid = $this->subscriptionQuoteItemHelper->getSubscriptionId($subscriptionItem)) {
                    $recurringOrder = true;
                    break;
                }
            }

            if ($recurringOrder) {
                $this->eventManager->dispatch(
                    'subscribe_pro_before_subscription_reorder_place',
                    [
                        'quote_id' => $cartId,
                        'quote' => $quote,
                    ]
                );
            }
        }

        return parent::placeOrder($cartId, $paymentMethod);
    }
}
