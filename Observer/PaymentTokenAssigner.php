<?php

namespace Swarming\SubscribePro\Observer;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Method\Vault;
use Swarming\SubscribePro\Gateway\Request\VaultDataBuilder;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Magento\Quote\Model\Quote\Payment as QuotePayment;

class PaymentTokenAssigner extends \Magento\Payment\Observer\AbstractDataAssignObserver
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

        if ($profileId === null) {
            return;
        }

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

        $paymentModel->setAdditionalInformation(
            Vault::TOKEN_METADATA_KEY,
            [
                PaymentTokenInterface::CUSTOMER_ID => $customerId,
                PaymentTokenInterface::PUBLIC_HASH => $paymentToken->getPublicHash()
            ]
        );
    }
}
