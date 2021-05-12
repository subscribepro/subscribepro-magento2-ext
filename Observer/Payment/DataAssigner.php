<?php

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;
use SubscribePro\Service\Transaction\TransactionInterface;

class DataAssigner extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    /**
     * @var array
     */
    protected $additionalInformationList = [
        PaymentDataBuilder::PAYMENT_METHOD_TOKEN,
        TransactionInterface::BROWSER_INFO,
    ];

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $key) {
            if (isset($additionalData[$key])) {
                $paymentInfo->setAdditionalInformation($key, $additionalData[$key]);
            }
        }
    }
}
