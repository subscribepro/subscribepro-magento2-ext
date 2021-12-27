<?php

namespace Swarming\SubscribePro\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;

class ThreeDSAuthorizeHandler implements HandlerInterface
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    private $platformPaymentProfileService;

    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var \Magento\Vault\Model\CreditCardTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var \Swarming\SubscribePro\Helper\Vault
     */
    private $vaultHelper;

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory
     * @param \Swarming\SubscribePro\Helper\Vault $vaultHelper
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory,
        \Swarming\SubscribePro\Helper\Vault $vaultHelper,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->vaultHelper = $vaultHelper;
        $this->subjectReader = $subjectReader;
        $this->logger = $logger;
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
        $transaction = $this->subjectReader->readTransaction($response);
        $transfer = $this->subjectReader->readTransferObject($handlingSubject);

        $this->saveVault($transaction->getRefPaymentProfileId());

        $transfer->setData('state', $transaction->getState());
        $transfer->setData('token', $transaction->getToken());
    }

    /**
     * @param int $paymentProfileId
     * @return void
     */
    private function saveVault($paymentProfileId)
    {
        try {
            $paymentToken = $this->paymentTokenFactory->create();
            $profile = $this->platformPaymentProfileService->loadProfile($paymentProfileId);
            $this->vaultHelper->initVault($paymentToken, $profile);
            $this->paymentTokenRepository->save($paymentToken);
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
