<?php

namespace Swarming\SubscribePro\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const KEY_ACTIVE = 'active';
    public const KEY_THREE_DS_ACTIVE = 'three_ds_active';
    public const KEY_WALLET_AUTHORIZATION_ACTIVE = 'wallet_authorization_active';
    public const KEY_WALLET_AUTHORIZATION_AMOUNT = 'wallet_authorization_amount';
    public const KEY_BROWSER_SIZE = 'browser_size';
    public const KEY_ACCEPT_HEADER = 'accept_header';
    public const KEY_ACTIVE_NON_SUBSCRIPTION = 'active_non_subscription';
    public const KEY_CURRENCY = 'currency';
    public const KEY_CC_TYPES = 'cctypes';
    public const KEY_CC_TYPES_MAPPER = 'cctypes_mapper';
    public const KEY_CC_USE_CCV = 'useccv';

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)$this->getValue(self::KEY_ACTIVE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isThreeDSActive($storeId = null)
    {
        return (bool)$this->getValue(self::KEY_THREE_DS_ACTIVE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isWalletAuthorizationActive($storeId = null)
    {
        return (bool)$this->getValue(self::KEY_WALLET_AUTHORIZATION_ACTIVE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return float
     */
    public function getWalletAuthorizationAmount($storeId = null)
    {
        return (float)$this->getValue(self::KEY_WALLET_AUTHORIZATION_AMOUNT, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getBrowserSize($storeId = null)
    {
        return $this->getValue(self::KEY_BROWSER_SIZE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getAcceptHeader($storeId = null)
    {
        return $this->getValue(self::KEY_ACCEPT_HEADER, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCurrency($storeId = null)
    {
        return $this->getValue(self::KEY_CURRENCY, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function hasVerification($storeId = null)
    {
        return (bool)$this->getValue(self::KEY_CC_USE_CCV, $storeId);
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
     * @return string[]
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
