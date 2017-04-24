<?php

namespace Swarming\SubscribePro\Ui\DataProvider\Product\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form;

class Subscription extends AbstractModifier
{
    const SUBSCRIPTION_ENABLED = 'subscription_enabled';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Catalog\Model\Locator\LocatorInterface
     */
    protected $locator;

    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    protected $arrayManager;

    /**
     * @param \Magento\Catalog\Model\Locator\LocatorInterface $locator
     * @param \Magento\Framework\Stdlib\ArrayManager $arrayManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $modelId = $this->locator->getProduct()->getId();

        if (!isset($data[$modelId][static::DATA_SOURCE_DEFAULT][static::SUBSCRIPTION_ENABLED])) {
            $data[$modelId][static::DATA_SOURCE_DEFAULT][static::SUBSCRIPTION_ENABLED] = '0';
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $this->customizeSubscriptionField($meta);
    }

    /**
     * @param array $meta
     * @return string[]
     */
    protected function customizeSubscriptionField(array $meta)
    {
        $switcherConfig = [
            'dataType' => Form\Element\DataType\Number::NAME,
            'formElement' => Form\Element\Checkbox::NAME,
            'componentType' => Form\Field::NAME,
            'prefer' => 'toggle',
            'valueMap' => [
                'true' => '1',
                'false' => '0'
            ],
        ];

        $path = $this->arrayManager->findPath(static::SUBSCRIPTION_ENABLED, $meta, null, 'children');
        $meta = $this->arrayManager->merge($path . static::META_CONFIG_PATH, $meta, $switcherConfig);

        return $meta;
    }
}
