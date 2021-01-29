<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Model\ApplePay;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

class Payment
{
    /**
     * @var CheckoutSession|SessionManagerInterface
     */
    private $checkoutSession;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SessionManagerInterface $checkoutSession,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    public function setPaymentToQuote()
    {
        return $this;
    }

    public function placeOrder()
    {
        return $this;
    }
}
