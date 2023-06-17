<?php

namespace Swarming\SubscribePro\Block\Customer;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Directory data processor.
 *
 * This class adds various country and region dictionaries to checkout page.
 * This data can be used by other UI components during checkout flow.
 */
class SubscriptionsDirectoryDataProcessor implements \Magento\Checkout\Block\Checkout\LayoutProcessorInterface
{
    /**
     * @var array
     */
    protected $countryOptions;

    /**
     * @var array
     */
    protected $regionOptions;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $regionCollectionFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $countryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DirectoryHelper
     */
    protected $directoryHelper;

    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection
     * @param StoreResolverInterface $storeResolver @deprecated
     * @param DirectoryHelper $directoryHelper
     * @param StoreManagerInterface $storeManager
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection,
        StoreResolverInterface $storeResolver,
        DirectoryHelper $directoryHelper,
        StoreManagerInterface $storeManager = null
    ) {
        $this->countryCollectionFactory = $countryCollection;
        $this->regionCollectionFactory = $regionCollection;
        $this->directoryHelper = $directoryHelper;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Process js Layout of block
     *
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        if (!isset($jsLayout['components']['spAddressProvider']['dictionaries'])) {
            $jsLayout['components']['spAddressProvider']['dictionaries'] = [
                'country_id' => $this->getCountryOptions(),
                'region_id' => $this->getRegionOptions(),
            ];
        }

        return $jsLayout;
    }

    /**
     * Get country options list.
     *
     * @return array
     */
    protected function getCountryOptions()
    {
        if (!isset($this->countryOptions)) {
            $this->countryOptions = $this->countryCollectionFactory->create()->loadByStore(
                $this->storeManager->getStore()->getId()
            )->toOptionArray();
            $this->countryOptions = $this->orderCountryOptions($this->countryOptions);
        }

        return $this->countryOptions;
    }

    /**
     * Get region options list.
     *
     * @return array
     */
    protected function getRegionOptions()
    {
        if (!isset($this->regionOptions)) {
            $this->regionOptions = $this->regionCollectionFactory->create()->addAllowedCountriesFilter(
                $this->storeManager->getStore()->getId()
            )->toOptionArray();
        }

        return $this->regionOptions;
    }

    /**
     * Sort country options by top country codes.
     *
     * @param array $countryOptions
     * @return array
     */
    protected function orderCountryOptions(array $countryOptions)
    {
        $topCountryCodes = $this->directoryHelper->getTopCountryCodes();
        if (empty($topCountryCodes)) {
            return $countryOptions;
        }

        $headOptions = [];
        $tailOptions = [[
            'value' => 'delimiter',
            'label' => '──────────',
            'disabled' => true,
        ]];
        foreach ($countryOptions as $countryOption) {
            if (empty($countryOption['value']) || in_array($countryOption['value'], $topCountryCodes)) {
                array_push($headOptions, $countryOption);
            } else {
                array_push($tailOptions, $countryOption);
            }
        }
        return array_merge($headOptions, $tailOptions);
    }
}
