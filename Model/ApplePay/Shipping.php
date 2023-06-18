<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\Region as DirectoryRegion;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;

class Shipping
{
    public const DEFAULT_FREE_METHOD = 'Free';

    /**
     * @var Quote
     */
    protected $quote;
    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface|null
     */
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
     * @var QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var QuoteResourceModel
     */
    private $quoteResourceModel;
    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    public function __construct(
        SessionManagerInterface $checkoutSession,
        DirectoryRegion $directoryRegion,
        Currency $currency,
        QuoteResourceModel $quoteResourceModel,
        JsonSerializer $jsonSerializer
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->directoryRegion = $directoryRegion;
        $this->currency = $currency;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|Quote
     */
    protected function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }

        return $this->quote;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDirectoryRegionByName($administrativeArea, $countryId)
    {
        return $this->directoryRegion->loadByName($administrativeArea, $countryId);
    }

    /**
     * @param array $shippingData
     * @return bool
     * @throws \Exception
     */
    public function setDataToQuote(array $shippingData): bool
    {
        // Retrieve the countryId from the request
        $countryId = ($shippingData['countryCode']) ?? null;
        $countryId = strtoupper($countryId);

        // Lookup region
        $region = $this->getDirectoryRegionByName($shippingData['administrativeArea'], $countryId);

        $this->getQuote()->getShippingAddress()
            ->setCountryId($countryId)
            ->setCity(($shippingData['locality']) ?? null)
            ->setPostcode(($shippingData['postalCode']) ?? null)
            ->setCollectShippingRates(true);
        if ($region->isEmpty()) {
            $this->getQuote()->getShippingAddress()->setRegionId($region->getId());
            $this->getQuote()->getShippingAddress()->setRegion($region->getName());
        }
        $this->getQuote()->getShippingAddress()->save();

        // Recalculate quote
        $this->getQuote()
            ->setTotalsCollectedFlag(false)
            ->collectTotals();

        $this->quoteResourceModel->save($this->getQuote());

        return true;
    }

    /**
     * @return array
     */
    public function getShippingMethods(): array
    {
        $quote = $this->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        // shipping rates
        $shippingRates = $shippingAddress
            ->collectShippingRates()
            ->getGroupedAllShippingRates();

        $rates = [];
        $currentRate = false;

        foreach ($shippingRates as $carrier => $groupRates) {
            foreach ($groupRates as $shippingRate) {
                // Is this the current selected shipping method?
                if ($quote->getShippingAddress()->getShippingMethod() == $shippingRate->getCode()) {
                    $currentRate = $this->convertShippingRate($shippingRate);
                } else {
                    $rates[] = $this->convertShippingRate($shippingRate);
                }
            }
        }

        // Add the current shipping rate first
        if ($currentRate) {
            array_unshift($rates, $currentRate);
        }

        return $rates;
    }

    /**
     * Convert a shipping rate into Apple Pay format
     *
     * @param $shippingRate
     * @return array
     */
    protected function convertShippingRate($shippingRate)
    {
        // Don't show the same information twice
        $detail = $shippingRate->getMethodTitle();
        if ($shippingRate->getCarrierTitle() == $detail || $detail === self::DEFAULT_FREE_METHOD) {
            $detail = '';
        }

        return [
            'label' => $shippingRate->getCarrierTitle(),
            'amount' => $this->formatPrice($shippingRate->getPrice()),
            'detail' => $detail,
            'identifier' => $shippingRate->getCode(),
        ];
    }

    /**
     * @param $applePayShippingMethod
     * @return $this
     * @throws \Exception
     */
    public function setShippingMethodToQuote($applePayShippingMethod)
    {
        if (isset($applePayShippingMethod['identifier'])) {
            // TODO: avoid deprecated methods.
            $this->getQuote()
                ->getShippingAddress()
                ->setShippingMethod($applePayShippingMethod['identifier']);

            $this->quoteResourceModel->save($this->getQuote());

            // Recalculate quote
            $this->getQuote()
                ->setTotalsCollectedFlag(false)
                ->collectTotals();

            $this->quoteResourceModel->save($this->getQuote());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function formatPrice($price)
    {
        return $this->currency->format($price, ['display'=>\Zend_Currency::NO_SYMBOL], false);
    }

    /**
     * @return array
     */
    public function getGrandTotal(): array
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
}
