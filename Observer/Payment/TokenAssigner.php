<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Observer\Payment;

use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;
use SubscribePro\Service\Transaction\TransactionInterface;

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

        $paymentProfileId = $additionalData['profile_id'] ?? null;
        if (empty($paymentProfileId)) {
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

        $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $paymentProfileId,
            $this->getPaymentMethodCode($additionalData),
            $customerId
        );
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

    /**
     * @param array $additionalData
     * @return string
     */
    private function getPaymentMethodCode(array $additionalData): string
    {
        $paymentMethodType = $additionalData['payment_method_type'] ?? '';

        switch ($paymentMethodType) {
            case 'apple_pay':
                $paymentMethodCode = ApplePayConfigProvider::CODE;
                break;
            case 'credit_card':
            default:
                $paymentMethodCode = ConfigProvider::CODE;
                break;

        }

        return $paymentMethodCode;
    }
}
