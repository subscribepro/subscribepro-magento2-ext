<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Exception;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class VaultDataBuilder implements BuilderInterface
{
    const PAYMENT_PROFILE_ID = 'profile_id';

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $tokenManagement;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $tokenManagement
    ) {
        $this->tokenManagement = $tokenManagement;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws Exception
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $publicHash = $payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH);

        $vault = $this->tokenManagement->getByPublicHash($publicHash, $order->getCustomerId());
        if (!$vault || !$vault->getIsActive()) {
            throw new Exception('The vault is not found.');
        }

        return [
            self::PAYMENT_PROFILE_ID => $vault->getGatewayToken()
        ];
    }
}
