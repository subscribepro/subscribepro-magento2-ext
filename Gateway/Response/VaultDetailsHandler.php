<?php

namespace Swarming\SubscribePro\Gateway\Response;

use SubscribePro\Service\Transaction\TransactionInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class VaultDetailsHandler implements HandlerInterface
{
    /**
     * @var \Magento\Vault\Model\CreditCardTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var \Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelper;

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @param \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory
     * @param \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param \Swarming\SubscribePro\Helper\Vault $vaultHelper
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory,
        \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        \Swarming\SubscribePro\Helper\Vault $vaultHelper,
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->vaultHelper = $vaultHelper;
        $this->gatewayConfig = $gatewayConfig;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDO->getPayment();

        if ($payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)) {
            $paymentToken = $this->getVaultPaymentToken($transaction);
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    protected function getVaultPaymentToken(TransactionInterface $transaction)
    {
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($transaction->getRefPaymentProfileId());

        $paymentToken->setExpiresAt($this->vaultHelper->getExpirationDate(
            $transaction->getCreditcardYear(),
            $transaction->getCreditcardMonth()
        ));

        $paymentToken->setTokenDetails($this->vaultHelper->getTokenDetails(
            $transaction->getCreditcardType(),
            $transaction->getCreditcardLastDigits(),
            $transaction->getCreditcardMonth(),
            $transaction->getCreditcardYear(),
            $transaction->getToken()
        ));

        return $paymentToken;
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
