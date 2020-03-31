<?php

namespace Swarming\SubscribePro\Api\Data;

/**
 * Subscribe Pro Subscription interface.
 *
 * @api
 */
interface SubscriptionInterface
{
    /**
     * Constants used as data array keys
     */
    const PRODUCT = 'product';

    const PRODUCT_OPTION = 'product_option';

    const PLATFORM_FIELD_KEY = 'magento2';

    /**
     * @return bool
     */
    public function isNew();

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface|null
     */
    public function getProduct();

    /**
     * @param \Swarming\SubscribePro\Api\Data\ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product);

    /**
     * @return mixed[]
     */
    public function getProductOption();

    /**
     * @param array $productOption
     * @return $this
     */
    public function setProductOption(array $productOption);

    /**
     * @return mixed[]
     */
    public function getUserDefinedFields();

    /**
     * @param array
     * @return $this
     */
    public function setUserDefinedFields(array $userDefinedFields);

    /**
     * @param int|null $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return string|null
     */
    public function getCustomerId();

    /**
     * @param string $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Subscription status: Active, Cancelled, Expired, Retry, Failed or Paused
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * @return string|null
     */
    public function getProductSku();

    /**
     * @return bool
     */
    public function getRequiresShipping();

    /**
     * @param bool $useShipping
     * @return $this
     */
    public function setRequiresShipping($useShipping);

    /**
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku);

    /**
     * @return mixed[]
     */
    public function getSubscriptionProducts();

    /**
     * @return int|null
     */
    public function getQty();

    /**
     * @param int $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * @return bool|null
     */
    public function getUseFixedPrice();

    /**
     * @param bool $useFixedPrice
     * @return $this
     */
    public function setUseFixedPrice($useFixedPrice);

    /**
     * @return float|null
     */
    public function getFixedPrice();

    /**
     * @param float $fixedPrice
     * @return $this
     */
    public function setFixedPrice($fixedPrice);

    /**
     * @return string|null
     */
    public function getInterval();

    /**
     * @param string $interval
     * @return $this
     */
    public function setInterval($interval);

    /**
     * @return string|null
     */
    public function getMagentoStoreCode();

    /**
     * @param string $magentoStoreCode
     * @return $this
     */
    public function setMagentoStoreCode($magentoStoreCode);

    /**
     * @return int|null
     */
    public function getPaymentProfileId();

    /**
     * @param int $paymentProfileId
     * @return $this
     */
    public function setPaymentProfileId($paymentProfileId);

    /**
     * @return \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    public function getPaymentProfile();

    /**
     * @return string|null
     */
    public function getAuthorizeNetPaymentProfileId();

    /**
     * @return string|null
     */
    public function getCreditcardLastDigits();

    /**
     * @return int|null
     */
    public function getMagentoBillingAddressId();

    /**
     * @return int|null
     */
    public function getShippingAddressId();

    /**
     * @param int $shippingAddressId
     * @return $this
     */
    public function setShippingAddressId($shippingAddressId);

    /**
     * @return \SubscribePro\Service\Address\AddressInterface|null
     */
    public function getShippingAddress();

    /**
     * @param \SubscribePro\Service\Address\AddressInterface|null $shippingAddress
     * @return $this
     */
    public function setShippingAddress($shippingAddress);

    /**
     * @return int|null
     */
    public function getMagentoShippingAddressId();

    /**
     * @return string|null
     */
    public function getMagentoShippingMethodCode();

    /**
     * @param string $magentoShippingMethodCode
     * @return $this
     */
    public function setMagentoShippingMethodCode($magentoShippingMethodCode);

    /**
     * @return bool|null
     */
    public function getSendCustomerNotificationEmail();

    /**
     * @param bool $sendCustomerNotificationEmail
     * @return $this
     */
    public function setSendCustomerNotificationEmail($sendCustomerNotificationEmail);

    /**
     * @return bool|null
     */
    public function getFirstOrderAlreadyCreated();

    /**
     * @param bool $firstOrderAlreadyCreated
     * @return $this
     */
    public function setFirstOrderAlreadyCreated($firstOrderAlreadyCreated);

    /**
     * @param string $nextOrderDate
     * @return $this
     */
    public function setNextOrderDate($nextOrderDate);

    /**
     * @param string|null $format
     * @return string|null
     */
    public function getNextOrderDate($format = null);

    /**
     * @param string|null $format
     * @return string|null
     */
    public function getLastOrderDate($format = null);

    /**
     * @param string|null $format
     * @return string|null
     */
    public function getExpirationDate($format = null);

    /**
     * @param string $expirationDate
     * @return $this
     */
    public function setExpirationDate($expirationDate);

    /**
     * @return string|null
     */
    public function getCouponCode();

    /**
     * @param string $couponCode
     * @return $this
     */
    public function setCouponCode($couponCode);

    /**
     * @param string|null $format
     * @return string|null
     */
    public function getErrorTime($format = null);

    /**
     * @return string|null
     */
    public function getErrorClass();

    /**
     * @return string|null
     */
    public function getErrorClassDescription();

    /**
     * @return string|null
     */
    public function getErrorType();

    /**
     * @return string|null
     */
    public function getErrorMessage();

    /**
     * @return int|null
     */
    public function getFailedOrderAttemptCount();

    /**
     * @param string|null $format
     * @return string|null
     */
    public function getRetryAfter($format = null);

    /**
     * @return int|null
     */
    public function getRecurringOrderCount();

    /**
     * @param string|null $format
     * @return string|null
     */
    public function getCreated($format = null);

    /**
     * @param string|null $format
     * @return string|null
     */
    public function getUpdated($format = null);

    /**
     * @param string|null $format
     * @return string|null
     */
    public function getCancelled($format = null);
}