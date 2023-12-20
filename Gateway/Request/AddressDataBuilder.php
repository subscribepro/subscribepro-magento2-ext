<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order\Address;
use SubscribePro\Service\Address\AddressInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class AddressDataBuilder implements BuilderInterface
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();
        $result = [];

        $billingAddress = $order->getBillingAddress();

        if ($billingAddress) {
            $result[PaymentProfileInterface::BILLING_ADDRESS] = [
                AddressInterface::FIRST_NAME => $billingAddress->getFirstname(),
                AddressInterface::LAST_NAME => $billingAddress->getLastname(),
                AddressInterface::COMPANY => $billingAddress->getCompany(),
                AddressInterface::STREET1 => $this->getStreetLine(1, $billingAddress),
                AddressInterface::STREET2 => $this->getStreetLine(2, $billingAddress),
                AddressInterface::STREET3 => $this->getStreetLine(3, $billingAddress),
                AddressInterface::CITY => $billingAddress->getCity(),
                AddressInterface::REGION => $billingAddress->getRegionCode(),
                AddressInterface::POSTCODE => $billingAddress->getPostcode(),
                AddressInterface::COUNTRY => $billingAddress->getCountryId(),
                AddressInterface::PHONE => $billingAddress->getTelephone(),
            ];
        }

        return $result;
    }

    /**
     * This offers compatibility with braintree since
     * \PayPal\Braintree\Gateway\Data\Order\OrderAdapter::getBillingAddress can return
     * AddressAdapterInterface|\Magento\Sales\Api\Data\OrderAddressInterface|null which have different accessors for
     * street line
     *
     * @param int $line
     * @param object $billingAddress
     * @return null|string
     */
    private function getStreetLine(int $line, object $billingAddress)
    {
        $streetLine = null;
        if (is_a($billingAddress, AddressAdapterInterface::class)) {
            $adapterMethod = 'getStreetLine' . $line;
            $streetLine = method_exists($billingAddress, $adapterMethod) ? $billingAddress->$adapterMethod() : null;
        } elseif (is_a($billingAddress, OrderAddressInterface::class)) {
            /** @var Address $billingAddress */
            $streetLine = $billingAddress->getStreetLine($line);
        }
        return $streetLine;
    }
}
