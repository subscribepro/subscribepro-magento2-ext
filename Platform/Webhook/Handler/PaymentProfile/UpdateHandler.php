<?php

namespace Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile;

use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;

class UpdateHandler extends AbstractHandler implements HandlerInterface
{
    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $platformPaymentProfileService;

    /**
     * @var \Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelper;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Swarming\SubscribePro\Helper\Vault $vaultHelper
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Swarming\SubscribePro\Helper\Vault $vaultHelper
    ) {
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        $this->vaultHelper = $vaultHelper;
        parent::__construct($paymentTokenManagement, $paymentTokenRepository, $customerRepository);
    }

    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(EventInterface $event)
    {
        $paymentToken = $this->getPaymentToken($event);
        $profile = $this->platformPaymentProfileService->createProfile((array)$event->getEventData('payment_profile'));
        $this->vaultHelper->updateVault($paymentToken, $profile);
        $this->paymentTokenRepository->save($paymentToken);
    }
}
