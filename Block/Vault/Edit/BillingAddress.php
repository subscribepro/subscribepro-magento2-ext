<?php

namespace Swarming\SubscribePro\Block\Vault\Edit;

use Magento\Store\Model\ScopeInterface;
use SubscribePro\Service\PaymentProfile\PaymentProfileInterface;

class BillingAddress extends \Magento\Directory\Block\Data
{
    public const XML_PATH_DISPLAY_ALL_STATES = 'general/region/display_all';

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     * @var \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    protected $profile;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        array $data = []
    ) {
        $this->regionFactory = $regionFactory;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
    }

    /**
     * @param \SubscribePro\Service\PaymentProfile\PaymentProfileInterface $profile
     * @return string
     */
    public function render(PaymentProfileInterface $profile = null)
    {
        $this->profile = $profile;
        $result = $this->toHtml();
        $this->profile = null;

        return $result;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getFirstName() : '';
    }

    /**
     * @return string
     */
    public function getMiddleName()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getMiddleName() : '';
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getLastName() : '';
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getCompany() : '';
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getPhone() : '';
    }

    /**
     * @return string
     */
    public function getStreetLine1()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getStreet1() : '';
    }

    /**
     * @return string
     */
    public function getStreetLine2()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getStreet2() : '';
    }

    /**
     * @return string
     */
    public function getStreetLine3()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getStreet3() : '';
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getCity() : '';
    }

    /**
     * @return string
     */
    public function getPostCode()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getPostcode() : '';
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->profile ? $this->profile->getBillingAddress()->getRegion() : '';
    }

    /**
     * @return int
     */
    public function getRegionId()
    {
        $regionCode = $this->profile ? $this->profile->getBillingAddress()->getRegion() : null;
        if ($regionCode) {
            $region = $this->regionFactory->create()->loadByCode($regionCode, $this->getCountryId());
            return $region->getRegionId();
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getCountryId()
    {
        $countryId = $this->profile ? $this->profile->getBillingAddress()->getCountry() : false;
        if ($countryId) {
            return $countryId;
        }
        return parent::getCountryId();
    }

    /**
     * @return bool
     */
    public function getIsRegionDisplayAll()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_DISPLAY_ALL_STATES, ScopeInterface::SCOPE_STORE);
    }
}
