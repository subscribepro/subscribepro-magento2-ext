<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CardDetailsHandler implements HandlerInterface
{
    const CARD_TYPE = 'cc_type';
    const CARD_NUMBER = 'cc_number';

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);

        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setCcLast4($transaction->getCreditcardLastDigits());
        $payment->setCcExpMonth($transaction->getCreditcardMonth());
        $payment->setCcExpYear($transaction->getCreditcardYear());

        $cardType = $this->gatewayConfig->getMappedCcType($transaction->getCreditcardType());
        $payment->setCcType($cardType);

        $payment->setAdditionalInformation(self::CARD_TYPE, $cardType);
        $payment->setAdditionalInformation(self::CARD_NUMBER, 'xxxx-' . $transaction->getCreditcardLastDigits());
    }
}
