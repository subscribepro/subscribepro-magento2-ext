<?php

namespace Swarming\SubscribePro\Model\Vault;

use Magento\Framework\Exception\LocalizedException;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

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
     * @var \Magento\Vault\Model\PaymentTokenFactory
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
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
     * @param \Swarming\SubscribePro\Helper\Vault $vaultHelper
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager
     * @param \Swarming\SubscribePro\Model\Vault\Validator $validator
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory,
        \Swarming\SubscribePro\Helper\Vault $vaultHelper,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Swarming\SubscribePro\Platform\Manager\Customer $platformCustomerManager,
        \Swarming\SubscribePro\Model\Vault\Validator $validator
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->vaultHelper = $vaultHelper;
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        $this->platformCustomerManager = $platformCustomerManager;
        $this->validator = $validator;
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

        if (!$this->validator->validate($profileData)) {
            throw new LocalizedException(__('Not all fields are filled.'));
        }

        $profile = $this->platformPaymentProfileService->createProfile();
        $profile->importData($profileData);
        $profile->setCustomerId($platformCustomer->getId());
        $profile->setMagentoCustomerId($platformCustomer->getMagentoCustomerId());
        $this->platformPaymentProfileService->saveToken($profileData['token'], $profile);

        $this->saveProfileToToken($profile);
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

        if (!$this->validator->validate($profileData)) {
            throw new LocalizedException(__('Not all fields are filled.'));
        }

        $profile = $this->platformPaymentProfileService->createProfile();
        $profile->importData($profileData);
        $profile->setId($paymentToken->getGatewayToken());
        $this->platformPaymentProfileService->saveProfile($profile);

        $this->saveProfileToToken($profile, $paymentToken);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface|null $paymentToken
     */
    protected function saveProfileToToken(PaymentProfileInterface $profile, PaymentTokenInterface $paymentToken = null)
    {
        $paymentToken = $paymentToken ?: $this->paymentTokenFactory->create();

        if ($paymentToken->isEmpty()) {
            $this->vaultHelper->initVault($paymentToken, $profile);
        } else {
            $this->vaultHelper->updateVault($paymentToken, $profile);
        }

        $this->paymentTokenRepository->save($paymentToken);
    }
}
