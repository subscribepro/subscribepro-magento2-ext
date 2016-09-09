<?php

namespace Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile;

use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;
use Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile\AbstractHandler;

class CreateHandler extends AbstractHandler implements HandlerInterface
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
     * @var \Magento\Vault\Model\PaymentTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Swarming\SubscribePro\Helper\Vault $vaultHelper
     * @param \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Swarming\SubscribePro\Helper\Vault $vaultHelper,
        \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
    ) {
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        $this->vaultHelper = $vaultHelper;
        $this->paymentTokenFactory = $paymentTokenFactory;
        parent::__construct($paymentTokenManagement, $paymentTokenRepository, $customerRepository);
    }

    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(EventInterface $event)
    {
        $paymentToken = $this->paymentTokenFactory->create();
        $profile = $this->platformPaymentProfileService->createProfile((array)$event->getEventData('payment_profile'));
        if (!$profile->getMagentoCustomerId()) {
            $profile->setMagentoCustomerId($this->getCustomerId($event));
        }
        $this->vaultHelper->initVault($paymentToken, $profile);
        $this->paymentTokenRepository->save($paymentToken);
    }
}
