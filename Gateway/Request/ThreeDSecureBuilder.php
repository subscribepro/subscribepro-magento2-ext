<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Config\Config as GatewayConfig;
use Swarming\SubscribePro\Model\Config\Source\ThreeDsType;

class ThreeDSecureBuilder implements BuilderInterface
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @param GatewayConfig $gatewayConfig
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->subjectReader = $subjectReader;
        $this->urlBuilder = $urlBuilder;
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
            $data[TransactionInterface::THREE_DS_REDIRECT_URL] = $this->urlBuilder->getUrl(
                'subscribepro/payment/status'
            );
            $data[TransactionInterface::THREE_DS_TYPE] = $this->gatewayConfig->getThreeDsType();
            $data[TransactionInterface::BROWSER_INFO] = $payment->getAdditionalInformation(
                TransactionInterface::BROWSER_INFO
            );
        }
        return $data;
    }
}
