<?php

namespace Swarming\SubscribePro\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Config\Share;

class PlatformField extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @return bool
     */
    protected function isShownCredentials()
    {
        $scope = $this->_scopeConfig->getValue(
            Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE,
            ScopeInterface::SCOPE_STORE
        );
        $isScopeGrobal = ($scope == Share::SHARE_GLOBAL && $this->getRequest()->getParam('website') == '');

        return $isScopeGrobal
            || $this->getRequest()->getParam('website')
            || $this->_storeManager->isSingleStoreMode();
    }

    /**
     * @return bool
     */
    protected function doRender()
    {
        return $this->isShownCredentials();
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->doRender() ? parent::render($element) :  '';
    }
}
