<?php

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

class DataAssigner extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    /**
     * @var array
     */
    protected $additionalInformationList = [
        PaymentDataBuilder::PAYMENT_METHOD_TOKEN,
        TransactionInterface::BROWSER_INFO,
        PaymentProfileInterface::CREDITCARD_FIRST_DIGITS,
        PaymentProfileInterface::CREDITCARD_LAST_DIGITS,
        PaymentProfileInterface::CREDITCARD_TYPE,
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
        $paymentInfo->setData(OrderPaymentInterface::CC_EXP_MONTH, $additionalData[OrderPaymentInterface::CC_EXP_MONTH] ?? '');
        $paymentInfo->setData(OrderPaymentInterface::CC_EXP_YEAR, $additionalData[OrderPaymentInterface::CC_EXP_YEAR] ?? '');
        $paymentInfo->setData(OrderPaymentInterface::CC_TYPE, $additionalData['creditcard_type'] ?? '');
    }
}
