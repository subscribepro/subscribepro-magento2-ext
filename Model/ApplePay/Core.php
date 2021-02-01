<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\Region as DirectoryRegion;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;

abstract class Core
{
    /**
     * @var Quote
     */
    protected $quote;
    protected $customerData;
    /**
     * @var SessionManagerInterface
     */
    protected $checkoutSession;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var Currency
     */
    protected $currency;
    /**
     * @var DirectoryRegion
     */
    protected $directoryRegion;
    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        SessionManagerInterface $checkoutSession,
        CustomerSession $customerSession,
        Currency $currency,
        DirectoryRegion $directoryRegion,
        JsonSerializer $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->currency = $currency;
        $this->directoryRegion = $directoryRegion;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|Quote
     */
    public function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    public function formatPrice($price)
    {
        return $this->currency->format($price, ['display'=>\Zend_Currency::NO_SYMBOL], false);
    }

    public function getDirectoryRegionByName($administrativeArea, $countryId)
    {
        return $this->directoryRegion->loadByName($administrativeArea, $countryId);
    }

    public function getDirectoryRegionByCode($administrativeArea, $countryId)
    {
        return $this->directoryRegion->loadByCode($administrativeArea, $countryId);
    }

    /**
     * @return array
     */
    public function getGrandTotal()
    {
        return [
            'label' => 'MERCHANT',
            'amount' => $this->formatPrice($this->getQuote()->getGrandTotal()),
        ];
    }

    /**
     * @return array
     */
    public function getRowItems(): array
    {
        $address = $this->getQuote()->getShippingAddress();
        return [
            [
                'label' => 'SUBTOTAL',
                'amount' => $this->formatPrice($address->getSubtotalWithDiscount()),
            ],
            [
                'label' => 'SHIPPING',
                'amount' => $this->formatPrice($address->getShippingAmount()),
            ],
            [
                'label' => 'TAX',
                'amount' => $this->formatPrice($address->getTaxAmount()),
            ],
        ];
    }

    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    public function getCustomerSession()
    {
        return $this->customerSession;
    }


    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerData()
    {
        if (null === $this->customerData) {
            $this->customerData = $this->getCustomerSession()->getCustomerData();
        }

        return $this->customerData;
    }
}
