<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\Region as DirectoryRegion;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;

class Shipping
{
    /**
     * @var CheckoutSession|SessionManagerInterface
     */
    private $checkoutSession;
    /**
     * @var Quote
     */
    private $quote;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var DirectoryRegion
     */
    private $directoryRegion;
    /**
     * @var Currency
     */
    private Currency $currency;

    public function __construct(
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository,
        DirectoryRegion $directoryRegion,
        Currency $currency
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->directoryRegion = $directoryRegion;
        $this->currency = $currency;
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

    public function setDataToQuote(array $shippingData)
    {
        // Retrieve the countryId from the request
        $countryId = ($shippingData['countryCode']) ?? null;
        $countryId = strtoupper($countryId);

        // Lookup region
        $region = $this->directoryRegion->loadByName($shippingData['administrativeArea'], $countryId);

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
            ->collectTotals()
            ->save();

        return true;
    }

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
        if ($shippingRate->getCarrierTitle() == $detail || $detail == 'Free') {
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
     * @return array
     */
    public function getGrandTotal()
    {
        return [
            'label' => 'MERCHANT',
            'amount' => $this->formatPrice($this->getQuote()->getGrandTotal()),
        ];
    }

    public function formatPrice($price)
    {
        return $this->currency->format($price, ['display'=>\Zend_Currency::NO_SYMBOL], false);
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

    public function setShippingMethodToQuote($applePayShippingMethod)
    {
        if (isset($applePayShippingMethod['identifier'])) {
            $this->getQuote()
                ->getShippingAddress()
                ->setShippingMethod($applePayShippingMethod['identifier'])
                ->save();

            // Recalculate quote
            $this->getQuote()
                ->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
        }

        return $this;
    }
}
