<?php

namespace Swarming\SubscribePro\Model\Config\Backend;

/**
 * Encrypted config field backend model.
 *
 * @api
 * @since 100.0.2
 */
class ValidJson extends \Magento\Framework\App\Config\Value
{
    /**
     * Encrypt value before saving
     *
     * @return void
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function beforeSave()
    {
        $jsonDecodedValue = json_decode($this->getValue(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Magento\Framework\Exception\ValidatorException(__('Invalid JSON value. ' . $this->getData('label')));
        }

        $this->setValue(json_encode($jsonDecodedValue, JSON_PRETTY_PRINT));
    }
}
