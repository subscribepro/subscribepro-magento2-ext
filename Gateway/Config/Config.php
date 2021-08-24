<?php

namespace Swarming\SubscribePro\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const KEY_THREE_DS_ACTIVE = 'three_ds_active';
    const KEY_THREE_DS_TYPE = 'three_ds_type';
    const KEY_WALLET_AUTHORIZATION_ACTIVE = 'wallet_authorization_active';
    const KEY_WALLET_AUTHORIZATION_AMOUNT = 'wallet_authorization_amount';
    const KEY_BROWSER_SIZE = 'browser_size';
    const KEY_ACCEPT_HEADER = 'accept_header';
    const KEY_ACTIVE_NON_SUBSCRIPTION = 'active_non_subscription';
    const KEY_CURRENCY = 'currency';
    const KEY_CC_TYPES = 'cctypes';
    const KEY_CC_TYPES_MAPPER = 'cctypes_mapper';
    const KEY_CC_USE_CCV = 'useccv';
    const KEY_WALLET_AUTHORIZATION_SUCCESS = 'succeeded';

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
     * @return string
     */
    public function getThreeDsType($storeId = null)
    {
        return $this->getValue(self::KEY_THREE_DS_TYPE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isWalletAuthorizationActive($storeId = null)
    {
        return $this->isThreeDSActive($storeId)
            || $this->getValue(self::KEY_WALLET_AUTHORIZATION_ACTIVE, $storeId);
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

    /**
     * @param string $state
     * @return bool
     */
    public function isWalletAuthorizationSuccess($transfer): bool
    {
        return $transfer->getData('state') === self::KEY_WALLET_AUTHORIZATION_SUCCESS &&
            $transfer->getData('amount') == ($this->getWalletAuthorizationAmount() * 100);
    }
}
