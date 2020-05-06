<?php

namespace Swarming\SubscribePro\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

/**
 * Encrypted config field backend model.
 *
 * @api
 * @since 100.0.2
 */
class ValidJson extends Value
{
    /**
     * Encrypt value before saving
     *
     * @return void
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $jsonDecodedValue = json_decode($this->getValue(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $field = $this->getFieldConfig();
            $label = $field && is_array($field) ? ' in field "' . $field['label'] . '"' : 'value';
            throw new ValidatorException(__('Invalid JSON ' . $label));
        }

        $this->setValue(json_encode($jsonDecodedValue, JSON_PRETTY_PRINT));
    }
}
