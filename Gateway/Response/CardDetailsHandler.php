<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;

class CardDetailsHandler implements HandlerInterface
{
    const CARD_NUMBER = 'cc_number';

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
        $payment->setCcType($transaction->getCreditcardType());

        $payment->unsAdditionalInformation('cc_cid');
        $payment->unsAdditionalInformation('cc_exp_year');
        $payment->unsAdditionalInformation('cc_exp_month');
        $payment->setAdditionalInformation(self::CARD_NUMBER, 'xxxx-' . $transaction->getCreditcardLastDigits());
    }
}
