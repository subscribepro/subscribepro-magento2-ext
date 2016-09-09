<?php

namespace Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile;

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
        $paymentToken = $this->getPaymentToken($event);
        $this->paymentTokenRepository->delete($paymentToken);
    }
}
