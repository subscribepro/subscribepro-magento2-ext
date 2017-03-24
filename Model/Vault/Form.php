<?php

namespace Swarming\SubscribePro\Model\Vault;

function get_debug_trace() {
    $traceArray = [];
    $trace = debug_backtrace();
    $caller = array_shift($trace);
    $function_name = $caller['function'];
    $traceArray[] = sprintf('%s: Called from %s:%s', $function_name, $caller['file'], $caller['line']);
    foreach ($trace as $entry_id => $entry) {
        $entry['file'] = $entry['file'] ? : '-';
        $entry['line'] = $entry['line'] ? : '-';
        if (empty($entry['class'])) {
            $traceArray[] = sprintf('%s %3s. %s() %s:%s', $function_name, $entry_id + 1, $entry['function'], $entry['file'], $entry['line']);
        } else {
            $traceArray[] = sprintf('%s %3s. %s->%s() %s:%s', $function_name, $entry_id + 1, $entry['class'], $entry['function'], $entry['file'], $entry['line']);
        }
    }
    return $traceArray;
}

use Magento\Framework\Exception\LocalizedException;

class Form
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var \Magento\Vault\Model\CreditCardTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var \Swarming\SubscribePro\Helper\Vault
     */
    protected $vaultHelper;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $platformPaymentProfileService;

    /**
     * @var \Swarming\SubscribePro\Platform\Manager\Customer
     */
    protected $platformCustomerManager;

    /**
     * @var \Swarming\SubscribePro\Model\Vault\Validator
     */
    protected $validator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory
     * @param \Swarming\SubscribePro\Helper\Vault $vaultHelper
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Swarming\SubscribePro\Model\Vault\Validator $validatora
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Vault\Model\CreditCardTokenFactory $paymentTokenFactory,
        \Swarming\SubscribePro\Helper\Vault $vaultHelper,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Swarming\SubscribePro\Model\Vault\Validator $validator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->vaultHelper = $vaultHelper;
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * @param array $profileData
     * @param int $customerId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createProfile(array $profileData, $customerId)
    {
        if (empty($profileData['token'])) {
            throw new LocalizedException(__('The credit card can be not saved.'));
        }
        $platformCustomer = $this->platformCustomerManager->getCustomerById($customerId, true);

        $profileData = $this->validator->validate($profileData);

        $profile = $this->platformPaymentProfileService->createProfile();
        $profile->importData($profileData);
        $profile->setCustomerId($platformCustomer->getId());
        $profile->setMagentoCustomerId($platformCustomer->getMagentoCustomerId());

        $this->platformPaymentProfileService->saveToken($profileData['token'], $profile);

        $paymentToken = $this->paymentTokenFactory->create();
        $this->vaultHelper->initVault($paymentToken, $profile);
        foreach(get_debug_trace() as $line) {
            $this->logger->critical($line);
        }
        $this->paymentTokenRepository->save($paymentToken);
        foreach(get_debug_trace() as $line) {
            $this->logger->critical($line);
        }
    }

    /**
     * @param string $publicHash
     * @param array $profileData
     * @param int $customerId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateProfile($publicHash, array $profileData, $customerId)
    {
        $paymentToken = $this->paymentTokenManagement->getByPublicHash($publicHash, $customerId);
        if (!$paymentToken) {
            throw new LocalizedException(__('The credit card is not found.'));
        }

        $profileData = $this->validator->validate($profileData);

        $profile = $this->platformPaymentProfileService->createProfile();
        $profile->importData($profileData);
        $profile->setId($paymentToken->getGatewayToken());
        $this->platformPaymentProfileService->saveProfile($profile);

        $this->vaultHelper->updateVault($paymentToken, $profile);
        foreach(get_debug_trace() as $line) {
            $this->logger->critical($line);
        }
        $this->paymentTokenRepository->save($paymentToken);
        foreach(get_debug_trace() as $line) {
            $this->logger->critical($line);
        }
    }
}
