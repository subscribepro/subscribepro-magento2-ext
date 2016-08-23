<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class CustomerDataBuilder implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        return [
            PaymentProfileInterface::MAGENTO_CUSTOMER_ID => $order->getCustomerId(),
        ];
    }
}
