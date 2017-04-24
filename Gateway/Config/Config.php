<?php

namespace Swarming\SubscribePro\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const KEY_ACTIVE_NON_SUBSCRIPTION = 'active_non_subscription';
    const KEY_CC_TYPES = 'cctypes';
    const KEY_CC_TYPES_MAPPER = 'cctypes_mapper';
    const KEY_CC_USE_CCV = 'useccv';

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function hasVerification($storeId = null)
    {
        return (bool) $this->getValue(self::KEY_CC_USE_CCV, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    public function getAvailableCardTypes($storeId = null)
    {
        $ccTypes = $this->getValue(self::KEY_CC_TYPES, $storeId);

        return !empty($ccTypes) ? explode(',', $ccTypes) : [];
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getCcTypesMapper($storeId = null)
    {
        $result = json_decode(
            $this->getValue(self::KEY_CC_TYPES_MAPPER, $storeId),
            true
        );

        return is_array($result) ? $result : [];
    }

    /**
     * @param string $cardType
     * @param int|null $storeId
     * @return string
     */
    public function getMappedCcType($cardType, $storeId = null)
    {
        $mapper = $this->getCcTypesMapper($storeId);
        return $cardType && isset($mapper[$cardType]) ? $mapper[$cardType] : $cardType;
    }
}
