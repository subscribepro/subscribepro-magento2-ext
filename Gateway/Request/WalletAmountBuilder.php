<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use SubscribePro\Service\Transaction\TransactionInterface;

class WalletAmountBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
    ) {
        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $authorizeAmount = (float)$this->formatPrice($this->gatewayConfig->getWalletAuthorizationAmount())*100;

        return [
            TransactionInterface::AMOUNT => $authorizeAmount,
            TransactionInterface::CURRENCY_CODE => $this->gatewayConfig->getCurrency(),
        ];
    }
}
