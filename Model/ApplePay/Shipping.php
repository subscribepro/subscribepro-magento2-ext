<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Swarming\SubscribePro\Model\ApplePay\Core as ApplePayCore;

class Shipping extends ApplePayCore
{
    const DEFAULT_FREE_METHOD = 'Free';
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
            ->collectTotals()
            ->save();

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
