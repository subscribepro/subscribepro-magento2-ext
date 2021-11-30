<?php

namespace Swarming\SubscribePro\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class PlatformMessage extends PlatformField
{
    /**
     * @return bool
     */
    protected function doRender()
    {
        return !$this->isShownCredentials();
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        // phpcs:ignore Generic.Files.LineLength.TooLong
        return __('Global Subscribe Pro account cannot be configured because "Share Customer Accounts" setting is set to "Per Website".')
            . ' '
            . __('It is necessary to set up separate Subscribe Pro account for each website.');
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderScopeLabel(AbstractElement $element)
    {
        return '';
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderInheritCheckbox(AbstractElement $element)
    {
        return '';
    }
}
