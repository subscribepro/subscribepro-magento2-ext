<?php

namespace Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile;

use Magento\Framework\Exception\NoSuchEntityException;
use SubscribePro\Service\Webhook\EventInterface;
use Swarming\SubscribePro\Gateway\Config\ApplePayConfigProvider;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;

class CreateHandler extends AbstractHandler implements HandlerInterface
{
    const PAYMENT_METHOD_CODE = 'apple_pay';

    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $platformPaymentProfileService;

    /**
     * @var \Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelper;

    /**
     * @var \Magento\Vault\Model\CreditCardTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Swarming\SubscribePro\Helper\Vault $vaultHelper
     * @param \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Swarming\SubscribePro\Helper\Vault $vaultHelper,
        \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory
    ) {
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        $this->vaultHelper = $vaultHelper;
        $this->paymentTokenFactory = $paymentTokenFactory;
        parent::__construct($paymentTokenManagement, $paymentTokenRepository, $customerRepository);
    }

    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     */
    public function execute(EventInterface $event)
    {
        // First make sure we don't already have this payment profile saved
        // This can happen if the customer creates the card record in Magento
        // since Subscribe Pro still sends a payment_profile.created webhook
        try {
            $paymentMethodCode = ConfigProvider::CODE;
            $paymentProfileData = $event->getEventData('payment_profile');
            if (isset($paymentProfileData['payment_method_type'])
                && $paymentProfileData['payment_method_type'] === self::PAYMENT_METHOD_CODE
            ) {
                $paymentMethodCode = ApplePayConfigProvider::CODE;
            }

            $this->getPaymentToken($event, $paymentMethodCode);
        } catch (NoSuchEntityException $e) {
            $paymentToken = $this->paymentTokenFactory->create();
            $profile = $this->platformPaymentProfileService->createProfile(
                (array)$event->getEventData('payment_profile')
            );
            if (!$profile->getMagentoCustomerId()) {
                $profile->setMagentoCustomerId($this->getCustomerId($event));
            }
            $this->vaultHelper->initVault($paymentToken, $profile, $paymentMethodCode);
            $this->paymentTokenRepository->save($paymentToken);
        }
    }
}
