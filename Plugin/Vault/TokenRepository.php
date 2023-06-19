<?php

namespace Swarming\SubscribePro\Plugin\Vault;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use SubscribePro\Exception\HttpException;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;

class TokenRepository
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $paymentProfileService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $paymentProfileService
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $paymentProfileService,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->paymentProfileService = $paymentProfileService;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $subject
     * @param \Closure $proceed
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @return bool
     */
    public function aroundDelete(
        PaymentTokenRepositoryInterface $subject,
        \Closure $proceed,
        PaymentTokenInterface $paymentToken
    ) {
        $result = $proceed($paymentToken);

        if ($paymentToken->getPaymentMethodCode() == ConfigProvider::CODE && $paymentToken->getGatewayToken()) {
            $this->redactProfile($paymentToken->getGatewayToken());
        }
        return $result;
    }

    /**
     * @param string|int|null $paymentProfileId
     */
    protected function redactProfile($paymentProfileId)
    {
        try {
            $profile = $this->paymentProfileService->loadProfile($paymentProfileId);
            if ($profile->getStatus() != PaymentProfileInterface::STATUS_REDACTED) {
                $this->paymentProfileService->redactProfile($paymentProfileId);
            }
        } catch (HttpException $e) {
            $this->logger->critical($e);
        }
    }
}
