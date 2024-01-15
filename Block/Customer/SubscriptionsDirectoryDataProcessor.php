<?php

namespace Swarming\SubscribePro\Block\Customer;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Directory data processor.
 *
 * This class adds various country and region dictionaries to checkout page.
 * This data can be used by other UI components during checkout flow.
 */
class SubscriptionsDirectoryDataProcessor implements LayoutProcessorInterface
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
     * @var CollectionFactory
     */
    protected $regionCollectionFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
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
     * @param CollectionFactory $regionCollection
     * @param DirectoryHelper $directoryHelper
     * @param StoreManagerInterface|null $storeManager
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollection,
        CollectionFactory $regionCollection,
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
     * @throws NoSuchEntityException
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
     * @throws NoSuchEntityException
     */
    protected function getCountryOptions()
    {
        if (!$this->countryOptions) {
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
     * @throws NoSuchEntityException
     */
    protected function getRegionOptions()
    {
        if (!$this->regionOptions) {
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
