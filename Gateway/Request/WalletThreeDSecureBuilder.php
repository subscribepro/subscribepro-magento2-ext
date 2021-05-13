<?php

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class WalletThreeDSecureBuilder implements BuilderInterface
{
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
        $data = [];
        if ($this->gatewayConfig->isThreeDSActive()) {
            $data[TransactionInterface::USE_THREE_DS] = 1;
            $data[TransactionInterface::BROWSER_INFO] = $this->getBrowserInfo($buildSubject);
        }

        return $data;
    }

    /**
     * @param array $buildSubject
     * @return string
     */
    private function getBrowserInfo(array $buildSubject)
    {
        if (empty($buildSubject['browser_info'])) {
            throw new \InvalidArgumentException('Browser info is not passed.');
        }
        return $buildSubject['browser_info'];
    }
}
