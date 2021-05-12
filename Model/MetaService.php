<?php

namespace Swarming\SubscribePro\Model;

class MetaService
{
    /**
     * @var \Swarming\SubscribePro\Model\MetaUserInterface
     */
    private $metaUser;

    /**
     * @var string
     */
    private $userType;

    /**
     * @param \Swarming\SubscribePro\Model\MetaUserInterface $metaUser
     * @param string $userType
     */
    public function __construct(
        \Swarming\SubscribePro\Model\MetaUserInterface $metaUser,
        string $userType
    ) {
        $this->metaUser = $metaUser;
        $this->userType = $userType;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $metadata = [];

        $changedBy = $this->getChangedBy();
        if (!empty($changedBy)) {
            $metadata['changed_by'] = $changedBy;
        }

        return $metadata;
    }

    /**
     * @return array|null
     */
    private function getChangedBy()
    {
        $userData = $this->metaUser->getMeta();
        return $userData ? [$this->userType => $userData] : null;
    }
}
