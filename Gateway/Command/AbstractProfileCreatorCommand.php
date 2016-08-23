<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Swarming\SubscribePro\Gateway\Request\PaymentDataBuilder;

abstract class AbstractProfileCreatorCommand extends AbstractCommand
{
    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Customer
     */
    protected $platformCustomerHelper;

    /**
     * @param \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param \Magento\Payment\Gateway\Response\HandlerInterface $handler
     * @param \Magento\Payment\Gateway\Validator\ValidatorInterface $validator
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Swarming\SubscribePro\Platform\Helper\Customer $platformCustomerHelper
     */
    public function __construct(
        \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder,
        \Swarming\SubscribePro\Platform\Platform $platform,
        \Magento\Payment\Gateway\Response\HandlerInterface $handler,
        \Magento\Payment\Gateway\Validator\ValidatorInterface $validator,
        \Psr\Log\LoggerInterface $logger,
        \Swarming\SubscribePro\Platform\Helper\Customer $platformCustomerHelper
    ) {
        $this->platformCustomerHelper = $platformCustomerHelper;
        parent::__construct($requestBuilder, $platform, $handler, $validator, $logger);
    }

    /**
     * @param array $requestData
     * @return PaymentProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createProfile(array $requestData)
    {
        if (empty($requestData[PaymentProfileInterface::MAGENTO_CUSTOMER_ID])) {
            throw new LocalizedException(__('Cannot create payment profile.'));
        }
        $platformCustomer = $this->platformCustomerHelper->getCustomer(
            $requestData[PaymentProfileInterface::MAGENTO_CUSTOMER_ID],
            true
        );

        $profile = $this->sdkPaymentProfileService->createProfile($requestData);
        $profile->setCustomerId($platformCustomer->getId());
        $this->sdkPaymentProfileService->saveToken($requestData[PaymentDataBuilder::PAYMENT_METHOD_TOKEN], $profile);

        return $profile;
    }
}
