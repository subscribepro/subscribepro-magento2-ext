<?php

namespace Swarming\SubscribePro\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class InstalledVersion extends Field
{
    public const INSTALLED_VERSION = '1.7.0';

    protected function _getElementHtml(AbstractElement $element): string
    {
        $element->setData('value', self::INSTALLED_VERSION);

        return '<strong>'
            . $element->getEscapedValue()
            . '</strong> - [<a href="https://github.com/subscribepro/subscribepro-magento2-ext/releases">'
            . 'View Releases'
            . '</a>]';
    }
}
