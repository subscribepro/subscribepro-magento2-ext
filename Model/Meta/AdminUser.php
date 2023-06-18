<?php

namespace Swarming\SubscribePro\Model\Meta;

class AdminUser implements \Swarming\SubscribePro\Model\MetaUserInterface
{
    public public const TYPE = 'admin';

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $backendAuthSession;

    /**
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession
    ) {
        $this->backendAuthSession = $backendAuthSession;
    }

    /**
     * @return array|null
     */
    public function getMeta()
    {
        $adminUser = $this->backendAuthSession->getUser();
        return $adminUser instanceof \Magento\User\Model\User ? $this->getAdminUserMeta($adminUser) : null;
    }

    /**
     * @param \Magento\User\Model\User $adminUser
     * @return array
     */
    private function getAdminUserMeta(\Magento\User\Model\User $adminUser): array
    {
        return [
            'user_id' => $adminUser->getId(),
            'email' => $adminUser->getEmail(),
            'full_name' => implode(' ', [$adminUser->getFirstName(), $adminUser->getLastName()]),
        ];
    }
}
