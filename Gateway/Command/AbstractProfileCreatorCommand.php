<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

abstract class AbstractProfileCreatorCommand extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManager;

    /**
     * @param \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Payment\Gateway\Response\HandlerInterface $handler
     * @param \Magento\Payment\Gateway\Validator\ValidatorInterface $validator
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Swarming\SubscribePro\Platform\Service\Transaction $platformTransactionService
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     */
    public function __construct(
        \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder,
        \Swarming\SubscribePro\Platform\Platform $platform,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Payment\Gateway\Response\HandlerInterface $handler,
        \Magento\Payment\Gateway\Validator\ValidatorInterface $validator,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Swarming\SubscribePro\Platform\Service\Transaction $platformTransactionService,
        \Psr\Log\LoggerInterface $logger,
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
    ) {
        $this->platformCustomerManager = $platformCustomerManager;
        parent::__construct(
            $requestBuilder,
            $platform,
            $storeManager,
            $subjectReader,
            $handler,
            $validator,
            $platformPaymentProfileService,
            $platformTransactionService,
            $logger
        );
    }

    /**
     * @param array $requestData
     * @return PaymentProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \SubscribePro\Exception\HttpException
     */
    protected function createProfile(array $requestData)
    {
        if (empty($requestData[PaymentProfileInterface::MAGENTO_CUSTOMER_ID])) {
            throw new LocalizedException(__('Cannot create payment profile.'));
        }
        $platformCustomer = $this->platformCustomerManager->getCustomerById(
            $requestData[PaymentProfileInterface::MAGENTO_CUSTOMER_ID],
            true
        );

        $profile = $this->platformPaymentProfileService->createProfile($requestData);
        $profile->setCustomerId($platformCustomer->getId());
        $this->platformPaymentProfileService->saveToken(
            $requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN],
            $profile
        );

        return $profile;
    }
}
