<?php

namespace Swarming\SubscribePro\Model\Vault;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Vault payment token repository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentTokenManagement extends \Magento\Vault\Model\PaymentTokenManagement
{
    /**
     * @inheritdoc
     */
    public function saveTokenWithPaymentLink(PaymentTokenInterface $token, OrderPaymentInterface $payment)
    {
        $tokenDuplicate = $this->getByGatewayToken(
            $token->getGatewayToken(),
            $token->getPaymentMethodCode(),
            $token->getCustomerId()
        );

        if (!empty($tokenDuplicate)) {
            if ($token->getIsVisible() || $tokenDuplicate->getIsVisible()) {
                $token->setEntityId($tokenDuplicate->getEntityId());
                $token->setIsVisible(true);
            } elseif ($token->getIsVisible() === $tokenDuplicate->getIsVisible()) {
                $token->setEntityId($tokenDuplicate->getEntityId());
            } else {
                $token->setPublicHash(
                    $this->encryptor->getHash(
                        $token->getPublicHash() . $token->getGatewayToken()
                    )
                );
            }
        }

        $this->paymentTokenRepository->save($token);

        $result = $this->addLinkToOrderPayment($token->getEntityId(), $payment->getEntityId());

        return $result;
    }
}
