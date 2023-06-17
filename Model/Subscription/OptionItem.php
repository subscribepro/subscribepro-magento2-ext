<?php

namespace Swarming\SubscribePro\Model\Subscription;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Framework\DataObject;

class OptionItem extends DataObject implements ItemInterface
{
    const PRODUCT = 'product';

    const ITEM = 'item';

    const CODE = 'code';

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
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @param \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface[] $options
     * @return $this
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addOption(OptionInterface $option)
    {
        $option->setData(self::ITEM, $this);

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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addOptionCode($option)
    {
        $this->optionsByCode[$option->getCode()] = $option;
        return $this;
    }

    /**
     * @param string $code
     * @return \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface|null
     */
    public function getOptionByCode($code)
    {
        return isset($this->optionsByCode[$code]) ? $this->optionsByCode[$code] : null;
    }
}
