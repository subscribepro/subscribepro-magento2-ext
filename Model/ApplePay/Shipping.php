<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\Region as DirectoryRegion;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
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

    /**
     * Construct Apple Pay shipping model.
     *
     * @param SessionManagerInterface $checkoutSession
     * @param DirectoryRegion         $directoryRegion
     * @param Currency                $currency
     * @param QuoteResourceModel      $quoteResourceModel
     * @param JsonSerializer          $jsonSerializer
     */
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
     * @return CartInterface|Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getQuote()
    {
        if (!$this->quote) {
            /** @var CheckoutSession $session */
            $session = $this->checkoutSession;
            $this->quote = $session->getQuote();
        }

        return $this->quote;
    }

    /**
     * @inheritdoc
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
        /** @var Quote $quote */
        $quote = $this->getQuote();
        // Retrieve the countryId from the request
        $countryId = ($shippingData['countryCode']) ?? null;
        $countryId = strtoupper($countryId);

        // Lookup region
        $region = $this->getDirectoryRegionByName($shippingData['administrativeArea'], $countryId);

        $quote->getShippingAddress()
            ->setCountryId($countryId)
            ->setCity(($shippingData['locality']) ?? null)
            ->setPostcode(($shippingData['postalCode']) ?? null)
            ->setCollectShippingRates(true);
        if ($region->isEmpty()) {
            $quote->getShippingAddress()->setRegionId($region->getId());
            $quote->getShippingAddress()->setRegion($region->getName());
        }
        $quote->getShippingAddress()->save();

        // Recalculate quote
        /* @phpstan-ignore-next-line */
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        $this->quoteResourceModel->save($quote);

        return true;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getShippingMethods(): array
    {
        /** @var Quote $quote */
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
     * @param Quote\Address\Rate $shippingRate
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
     * @param array|null $applePayShippingMethod
     * @return $this
     * @throws \Exception
     */
    public function setShippingMethodToQuote($applePayShippingMethod)
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        if (isset($applePayShippingMethod['identifier'])) {
            $quote
                ->getShippingAddress()
                ->setShippingMethod($applePayShippingMethod['identifier']);

            $this->quoteResourceModel->save($this->getQuote());

            // Recalculate quote
            /* @phpstan-ignore-next-line */
            $quote->setTotalsCollectedFlag(false)->collectTotals();

            $this->quoteResourceModel->save($this->getQuote());
        }

        return $this;
    }

    /**
     * @param $price
     * @return string
     */
    public function formatPrice($price)
    {
        return $this->currency->format(
            $price,
            ['display' => \Magento\Framework\Currency\Data\Currency::NO_SYMBOL],
            false
        );
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getGrandTotal(): array
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        return [
            'label' => 'MERCHANT',
            'amount' => $this->formatPrice($quote->getGrandTotal()),
        ];
    }

    /**
     * @return array[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getRowItems(): array
    {
        /** @var Quote $quote */
        $quote = $this->getQuote();
        $address = $quote->getShippingAddress();
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
