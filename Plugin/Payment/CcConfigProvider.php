<?php

namespace Swarming\SubscribePro\Plugin\Payment;

use Magento\Framework\View\Asset\Source;
use Magento\Payment\Model\CcConfig;

class CcConfigProvider
{
    /**
     * @var array
     */
    private array $icons = [];

    /**
     * @var CcConfig
     */
    protected CcConfig $ccConfig;

    /**
     * @var Source
     */
    protected Source $assetSource;

    /**
     * @param CcConfig $ccConfig
     * @param Source $assetSource
     */
    public function __construct(
        CcConfig $ccConfig,
        Source $assetSource
    ) {
        $this->ccConfig = $ccConfig;
        $this->assetSource = $assetSource;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetIcons($subject, callable $proceed)
    {
        if (!empty($this->icons)) {
            return $this->icons;
        }

        $types = $this->ccConfig->getCcAvailableTypes();
        foreach ($types as $code => $label) {
            if (!array_key_exists($code, $this->icons)) {
                $asset = $this->ccConfig->createAsset('Swarming_SubscribePro::images/cc/' . strtolower($code) . '.svg');
                $placeholder = $this->assetSource->findSource($asset);
                if ($placeholder) {
                    list($width, $height) = getimagesize($asset->getSourceFile());
                    $this->icons[$code] = [
                        'url' => $asset->getUrl(),
                        'width' => $width,
                        'height' => $height,
                        'title' => __($label),
                    ];
                }
            }
        }

        return $this->icons;
    }
}
