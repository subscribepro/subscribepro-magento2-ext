<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use SubscribePro\Service\Transaction\TransactionInterface;
use Swarming\SubscribePro\Model\Config\Source\ThreeDsType;

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
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $paymentProfileService;

    /**
     * @param \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory
     * @param \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param \Swarming\SubscribePro\Helper\Vault $vaultHelper
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $paymentProfileService
     */
    public function __construct(
        \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory,
        \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        \Swarming\SubscribePro\Helper\Vault $vaultHelper,
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $paymentProfileService
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->vaultHelper = $vaultHelper;
        $this->gatewayConfig = $gatewayConfig;
        $this->subjectReader = $subjectReader;
        $this->paymentProfileService = $paymentProfileService;
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
            $paymentToken = $this->getVaultPaymentToken($transaction, $payment->getIsTransactionPending());
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * @param \SubscribePro\Service\Transaction\TransactionInterface $transaction
     * @param bool $isPending
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    protected function getVaultPaymentToken(TransactionInterface $transaction, $isPending = false)
    {
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($transaction->getRefPaymentProfileId());

        $paymentToken->setExpiresAt($this->vaultHelper->getExpirationDate(
            $transaction->getCreditcardYear(),
            $transaction->getCreditcardMonth()
        ));

        /** @var \Swarming\SubscribePro\Platform\Service\PaymentProfile */
        $paymentProfile = $this->paymentProfileService->loadProfile($transaction->getRefPaymentProfileId());
        $vaultPaymentToken = $paymentProfile->getPaymentToken();

        $tokenDetails = $this->vaultHelper->getTokenDetails(
            $transaction->getCreditcardType(),
            $transaction->getCreditcardLastDigits(),
            $transaction->getCreditcardMonth(),
            $transaction->getCreditcardYear(),
            $vaultPaymentToken
        );
        if ($this->gatewayConfig->getThreeDsType() === ThreeDsType::GATEWAY_INDEPENDENT && $isPending) {
            $tokenDetails = $this->vaultHelper->markPendingTokenDetails($tokenDetails);
        }
        $paymentToken->setTokenDetails($tokenDetails);

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
