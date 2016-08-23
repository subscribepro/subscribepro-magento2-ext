<?php

namespace Swarming\SubscribePro\Service;

use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\ResourceModel\PaymentToken as PaymentTokenResourceModel;
use Swarming\SubscribePro\Api\PaymentTokenManagementInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Magento\Vault\Api\PaymentTokenManagementInterface as VaultPaymentTokenManagementInterface;

class PaymentTokenManagement implements PaymentTokenManagementInterface
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface|PaymentTokenManagementInterface
     */
    protected $vaultPaymentTokenManagement;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $vaultPaymentTokenManagement
     */
    public function __construct(VaultPaymentTokenManagementInterface $vaultPaymentTokenManagement)
    {
        $this->vaultPaymentTokenManagement = $vaultPaymentTokenManagement;
    }

    /**
     * @param int $customerId Customer ID.
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface[]
     */
    public function getSubscribeProTokensByCustomerId($customerId)
    {
        $entities = $this->vaultPaymentTokenManagement->getListByCustomerId($customerId);

        return array_filter($entities, function (PaymentTokenInterface $paymentToken) {
            return $paymentToken->getPaymentMethodCode() == ConfigProvider::CODE
                   && $paymentToken->getIsActive()
                   && $paymentToken->getIsVisible();
        });
    }
}
