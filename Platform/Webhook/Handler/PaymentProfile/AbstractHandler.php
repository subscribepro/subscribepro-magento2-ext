<?php

namespace Swarming\SubscribePro\Platform\Webhook\Handler\PaymentProfile;

use SubscribePro\Service\Customer\CustomerInterface as PlatformCustomerInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use SubscribePro\Service\Webhook\EventInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Swarming\SubscribePro\Platform\Webhook\HandlerInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;

abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     * @param string                                       $paymentMethodCode
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     * @throws NoSuchEntityException
     */
    protected function getPaymentToken(EventInterface $event, string $paymentMethodCode = ConfigProvider::CODE)
    {
        $customerId = $this->getCustomerId($event);
        $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $event->getEventData('payment_profile_id'),
            $paymentMethodCode,
            $customerId
        );

        if (!$paymentToken) {
            throw new NoSuchEntityException(
                __('Saved card is not found for payment_id=%1 and customer_id=%2', $event->getEventData('payment_profile_id'), $customerId)
            );
        }

        return $paymentToken;
    }

    /**
     * @param \SubscribePro\Service\Webhook\EventInterface $event
     * @return int
     */
    protected function getCustomerId(EventInterface $event)
    {
        $paymentProfileData = $event->getEventData('payment_profile');
        if (!empty($paymentProfileData[PaymentProfileInterface::MAGENTO_CUSTOMER_ID])) {
            return $paymentProfileData[PaymentProfileInterface::MAGENTO_CUSTOMER_ID];
        }

        $customerData = $event->getEventData('customer');
        if (!empty($customerData[PlatformCustomerInterface::MAGENTO_CUSTOMER_ID])) {
            return $customerData[PlatformCustomerInterface::MAGENTO_CUSTOMER_ID];
        }

        $customerEmail = !empty($paymentProfileData[PaymentProfileInterface::CUSTOMER_EMAIL])
            ? $paymentProfileData[PaymentProfileInterface::CUSTOMER_EMAIL]
            : $customerData[PlatformCustomerInterface::EMAIL];

        return $this->customerRepository->get($customerEmail)->getId();
    }
}
