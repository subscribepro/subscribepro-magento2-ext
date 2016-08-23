<?php

namespace Swarming\SubscribePro\Model\Vault;

use Magento\Framework\Exception\LocalizedException;

class Form
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var \SubscribePro\Service\PaymentProfile\PaymentProfileService
     */
    protected $sdkPaymentProfileService;

    /**
     * @var \Magento\Vault\Model\PaymentTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var \Swarming\SubscribePro\Model\Vault\Manager
     */
    protected $vaultManager;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Customer
     */
    protected $platformCustomerHelper;

    /**
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
     * @param \Swarming\SubscribePro\Model\Vault\Manager $vaultManager
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Swarming\SubscribePro\Platform\Helper\Customer $platformCustomerHelper
     */
    public function __construct(
        \Magento\Customer\Model\Session $session,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Swarming\SubscribePro\Platform\Platform $platform,
        \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory,
        \Swarming\SubscribePro\Model\Vault\Manager $vaultManager,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Swarming\SubscribePro\Platform\Helper\Customer $platformCustomerHelper
    ) {
        $this->session = $session;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->sdkPaymentProfileService = $platform->getSdk()->getPaymentProfileService();
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->vaultManager = $vaultManager;
        $this->regionFactory = $regionFactory;
        $this->platformCustomerHelper = $platformCustomerHelper;
    }

    /**
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createProfile(array $data)
    {
        if (empty($data['payment']['token'])) {
            throw new LocalizedException(__('The credit card can be not saved.'));
        }
        $paymentProfileToken = $data['payment']['token'];

        $paymentProfileData = $data['payment'];
        $paymentProfileData['billing_address'] = $this->updateBillingRegion($data['billing_address']);

        $platformCustomer = $this->platformCustomerHelper->getCustomer($this->session->getCustomerId(), true);

        $profile = $this->sdkPaymentProfileService->createProfile();
        $profile->importData($paymentProfileData);
        $profile->setCustomerId($platformCustomer->getId());
        $profile->setMagentoCustomerId($platformCustomer->getMagentoCustomerId());
        $this->sdkPaymentProfileService->saveToken($paymentProfileToken, $profile);

        $paymentToken = $this->paymentTokenFactory->create();
        $this->vaultManager->initVault($paymentToken, $profile);
        $this->paymentTokenRepository->save($paymentToken);
    }

    /**
     * @param string $publicHash
     * @param array $profileData
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateProfile($publicHash, array $profileData)
    {
        $paymentToken = $this->paymentTokenManagement->getByPublicHash($publicHash, $this->session->getCustomerId());
        if (!$paymentToken) {
            throw new LocalizedException(__('The credit card is not found.'));
        }

        $profileData['billing_address'] = $this->updateBillingRegion($profileData['billing_address']);
        $profile = $this->sdkPaymentProfileService->createProfile($profileData);
        $profile->setId($paymentToken->getGatewayToken());
        $this->sdkPaymentProfileService->saveProfile($profile);

        $this->vaultManager->updateVault($paymentToken, $profile);
        $this->paymentTokenRepository->save($paymentToken);
    }

    /**
     * @param array $billingData
     * @return array
     */
    protected function updateBillingRegion(array $billingData)
    {
        if (empty($billingData['region_id']) || empty($billingData['country'])) {
            return $billingData;
        }

        $region = $this->regionFactory->create()->load($billingData['region_id']);
        if ($region->getCode() && $region->getCountryId() == $billingData['country']) {
            $billingData['region'] = $region->getCode();
        }
        return $billingData;
    }
}
