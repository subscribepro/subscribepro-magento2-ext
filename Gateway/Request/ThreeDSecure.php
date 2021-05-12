<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;

class ThreeDSecure implements BuilderInterface
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();
        $paymentMethod = $payment->getMethodInstance();

        $data = [];
        if ($paymentMethod->getConfigData(GatewayConfig::KEY_THREE_DS_ACTIVE)) {
            $data[TransactionInterface::USE_THREE_DS] = true;
            $data[TransactionInterface::BROWSER_INFO] = $payment->getAdditionalInformation(TransactionInterface::BROWSER_INFO);
        }
        return $data;
    }
}
