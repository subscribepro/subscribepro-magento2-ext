<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;

class CardDetailsHandler implements HandlerInterface
{
    const CARD_TYPE = 'cc_type';
    const CARD_NUMBER = 'cc_number';

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $config;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $config
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $transaction = SubjectReader::readTransaction($response);

        /**
         * @TODO after changes in sales module should be refactored for new interfaces
         */
        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setCcLast4($transaction->getCreditcardLastDigits());
        $payment->setCcExpMonth($transaction->getCreditcardMonth());
        $payment->setCcExpYear($transaction->getCreditcardYear());

        $cardType = $this->config->getMappedCcType($transaction->getCreditcardType());
        $payment->setCcType($cardType);

        $payment->setAdditionalInformation(self::CARD_TYPE, $cardType);
        $payment->setAdditionalInformation(self::CARD_NUMBER, 'xxxx-' . $transaction->getCreditcardLastDigits());
    }
}
