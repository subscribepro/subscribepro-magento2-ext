<?php

namespace Swarming\SubscribePro\Gateway\Response;

use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class VaultDetailsHandler implements HandlerInterface
{
    /**
     * @var \Magento\Vault\Api\Data\PaymentTokenInterfaceFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $config;

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterfaceFactory $paymentTokenFactory
     * @param \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param \Swarming\SubscribePro\Gateway\Config\Config $config
     */
    public function __construct(
        \Magento\Vault\Api\Data\PaymentTokenInterfaceFactory $paymentTokenFactory,
        \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        \Swarming\SubscribePro\Gateway\Config\Config $config
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->config = $config;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $transaction = SubjectReader::readTransaction($response);
        $payment = $paymentDO->getPayment();

        if ($payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)) {
            $paymentToken = $this->getVaultPaymentToken($transaction);
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    protected function getVaultPaymentToken(TransactionInterface $transaction)
    {
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($transaction->getRefPaymentProfileId());
        $paymentToken->setExpiresAt($this->getExpirationDate($transaction));

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $this->config->getMappedCcType($transaction->getCreditcardType()),
            'maskedCC' => $transaction->getCreditcardLastDigits(),
            'expirationDate' => $transaction->getCreditcardMonth() . '/' . $transaction->getCreditcardYear()
        ]));

        return $paymentToken;
    }

    /**
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @return string
     */
    protected function getExpirationDate(TransactionInterface $transaction)
    {
        $expDate = new \DateTime(
            $transaction->getCreditcardYear()
            . '-'
            . $transaction->getCreditcardMonth()
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );
        $expDate->add(new \DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * @param array $details
     * @return string
     */
    protected function convertDetailsToJSON($details)
    {
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \Magento\Sales\Api\Data\OrderPaymentExtensionInterface
     */
    protected function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
