<?php

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Magento\Quote\Model\Quote\Payment as QuotePayment;

class TokenAssigner extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData) || !isset($additionalData[VaultDataBuilder::PAYMENT_PROFILE_ID])) {
            return;
        }

        $profileId = $additionalData[VaultDataBuilder::PAYMENT_PROFILE_ID];

        /** @var \Magento\Quote\Model\Quote\Payment $paymentModel */
        $paymentModel = $this->readPaymentModelArgument($observer);
        if (!$paymentModel instanceof QuotePayment) {
            return;
        }

        $quote = $paymentModel->getQuote();
        $customerId = $quote->getCustomer()->getId();
        if ($customerId === null) {
            return;
        }

        $paymentToken = $this->paymentTokenManagement->getByGatewayToken($profileId, ConfigProvider::CODE, $customerId);
        if ($paymentToken === null) {
            return;
        }

        $paymentModel->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $customerId);
        $paymentModel->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $paymentToken->getPublicHash());

        if (!empty($additionalData[TransactionInterface::UNIQUE_ID])) {
            $paymentModel->setAdditionalInformation(
                TransactionInterface::UNIQUE_ID,
                $additionalData[TransactionInterface::UNIQUE_ID]
            );
        }

        if (!empty($additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN])) {
            $paymentModel->setAdditionalInformation(
                TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN,
                $additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN]
            );
        }
    }
}
