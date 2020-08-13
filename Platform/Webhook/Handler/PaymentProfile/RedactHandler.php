<?php

namespace Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile;

use Magento\Framework\Exception\NoSuchEntityException;
use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;
use Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile\AbstractHandler;

class RedactHandler extends AbstractHandler implements HandlerInterface
{
    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(EventInterface $event)
    {
        // First make sure the payment token we're trying to redact exists
        try {
            $paymentToken = $this->getPaymentToken($event);
        } catch (NoSuchEntityException $e) {
            // Don't return an error because we don't want to hang up redact requests from SP
            // if the payment profile already doesn't exist in Magento
            // Instead, just don't try to delete it
            return;
        }

        $this->paymentTokenRepository->delete($paymentToken);
    }
}
