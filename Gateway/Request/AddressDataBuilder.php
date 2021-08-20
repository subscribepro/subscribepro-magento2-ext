<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Address\AddressInterface;

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
                AddressInterface::STREET1 => $billingAddress->getStreetLine1(),
                AddressInterface::STREET2 => $billingAddress->getStreetLine2(),
                AddressInterface::STREET3 => $billingAddress->getStreetLine3(),
                AddressInterface::CITY => $billingAddress->getCity(),
                AddressInterface::REGION => $billingAddress->getRegionCode(),
                AddressInterface::POSTCODE => $billingAddress->getPostcode(),
                AddressInterface::COUNTRY => $billingAddress->getCountryId()
            ];
        }

        return $result;
    }
}
