<?php

namespace Swarming\SubscribePro\Model\Subscription;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item\Option;

class OptionItem extends DataObject implements ItemInterface
{
    public const PRODUCT = 'product';

    public const ITEM = 'item';

    public const CODE = 'code';

    /**
     * @var \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface[]
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $optionsByCode = [];

    //@codeCoverageIgnoreStart

    /**
     * @return \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface[]
     */
    public function getOptionsByCode()
    {
        return $this->optionsByCode;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return $this
     */
    public function setProduct(ProductInterface $product)
    {
        return $this->setData(self::PRODUCT, $product);
    }

    /**
     * @return null
     */
    public function getFileDownloadParams()
    {
        return null;
    }

    //@codeCoverageIgnoreEnd

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws LocalizedException
     */
    public function getProduct()
    {
        $product = $this->_getData(self::PRODUCT);
        if (!$product) {
            return null;
        }

        $product->setFinalPrice(null);
        $product->setCustomOptions($this->optionsByCode);

        return $product;
    }

    /**
     * @param $options
     * @return $this
     * @throws LocalizedException
     */
    public function setOptions($options)
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface $option
     * @return $this
     * @throws LocalizedException
     */
    public function addOption(OptionInterface $option)
    {
        /** @var Option $option */
        $option->setData(self::ITEM, $this);
        /** @var Option $existingOption */
        $existingOption = $this->getOptionByCode($option->getCode());
        if ($existingOption) {
            $existingOption->addData($option->getData());
        } else {
            $this->addOptionCode($option);
            $this->options[] = $option;
        }
        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface $option
     * @return $this
     * @throws LocalizedException
     */
    protected function addOptionCode($option)
    {
        /** @var Option $option */
        $this->optionsByCode[$option->getCode()] = $option;
        return $this;
    }

    /**
     * @param string $code
     * @return \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface|null
     */
    public function getOptionByCode($code)
    {
        return $this->optionsByCode[$code] ?? null;
    }
}
