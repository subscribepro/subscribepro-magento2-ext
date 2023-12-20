<?php

namespace Swarming\SubscribePro\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class TestConnection extends PlatformField
{
    /**
     * @var string
     */
    protected $_template = 'system/config/test_connection.phtml';

    /**
     * @var string
     */
    protected string $_htmlId;

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setHtmlId($element->getHtmlId());
        return $this->_toHtml();
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getButtonLabel()
    {
        return __('Test connection');
    }

    /**
     * @return string
     */
    public function getBaseUrlSelectorId()
    {
        return 'swarming_subscribepro_platform_base_url';
    }

    /**
     * @return string
     */
    public function getClientIdSelectorId()
    {
        return 'swarming_subscribepro_platform_client_id';
    }

    /**
     * @return string
     */
    public function getClientSecretSelectorId()
    {
        return 'swarming_subscribepro_platform_client_secret';
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->_urlBuilder->getUrl('swarming_subscribepro/config/testConnection');
    }

    /**
     * @return string
     */
    public function getWebsiteCode()
    {
        return $this->getRequest()->getParam('website', '');
    }

    /**
     * Set html id
     *
     * @param string $htmlId
     * @return void
     */
    public function setHtmlId(string $htmlId): void
    {
        $this->_htmlId = $htmlId;
    }
}
