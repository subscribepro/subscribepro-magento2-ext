<?php

namespace Swarming\SubscribePro\Platform\Service;

use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

/**
 * @method \SubscribePro\Service\PaymentProfile\PaymentProfileService getService($websiteId = null)
 */
class PaymentProfile extends AbstractService
{
    /**
     * @var \Swarming\SubscribePro\Model\MetaService
     */
    private $metaService;

    /**
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param string $name
     * @param \Swarming\SubscribePro\Model\MetaService $metaService
     */
    public function __construct(
        \Swarming\SubscribePro\Platform\Platform $platform,
        string $name,
        \Swarming\SubscribePro\Model\MetaService $metaService
    ) {
        $this->metaService = $metaService;
        parent::__construct($platform, $name);
    }

    /**
     * @param array $paymentProfileData
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function createProfile(array $paymentProfileData = [], $websiteId = null)
    {
        return $this->getService($websiteId)->createProfile($paymentProfileData);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveProfile(PaymentProfileInterface $paymentProfile, $websiteId = null)
    {
        return $this->getService($websiteId)->saveProfile($paymentProfile);
    }

    /**
     * @param int $paymentProfileId
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function redactProfile($paymentProfileId, $websiteId = null)
    {
        return $this->getService($websiteId)->redactProfile($paymentProfileId);
    }

    /**
     * @param int $paymentProfileId
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProfile($paymentProfileId, $websiteId = null)
    {
        return $this->getService($websiteId)->loadProfile($paymentProfileId);
    }

    /**
     * Retrieve an array of all payment profiles.
     *  Available filters:
     * - magento_customer_id
     * - customer_email
     *
     * @param array $filters
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface[]
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProfiles(array $filters = [], $websiteId = null)
    {
        return $this->getService($websiteId)->loadProfiles($filters);
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function saveThirdPartyToken(PaymentProfileInterface $paymentProfile, $websiteId = null)
    {
        return $this->getService($websiteId)->saveThirdPartyToken($paymentProfile);
    }

    /**
     * @param string $token
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function saveToken($token, PaymentProfileInterface $paymentProfile, $websiteId = null)
    {
        $metadata = $this->metaService->getData();
        return $this->getService($websiteId)->saveToken($token, $paymentProfile, $metadata);
    }

    /**
     * @param string $token
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $paymentProfile
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \SubscribePro\Exception\EntityInvalidDataException
     * @throws \SubscribePro\Exception\HttpException
     */
    public function verifyAndSaveToken($token, PaymentProfileInterface $paymentProfile, $websiteId = null)
    {
        return $this->getService($websiteId)->verifyAndSaveToken($token, $paymentProfile);
    }

    /**
     * @param string $token
     * @param int|null $websiteId
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     * @throws \SubscribePro\Exception\HttpException
     */
    public function loadProfileByToken($token, $websiteId = null)
    {
        return $this->getService($websiteId)->loadProfileByToken($token);
    }
}
