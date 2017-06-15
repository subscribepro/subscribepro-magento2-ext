<?php

namespace Swarming\SubscribePro\Block\Adminhtml\System\Config;

class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\ConfigProvider
     */
    protected $gatewayConfigProvider;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $quoteSession;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        array $data = []
    ) {
        $this->gatewayConfigProvider = $gatewayConfigProvider;
        $this->quoteSession = $quoteSession;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getPaymentConfig()
    {
        $storeId = $this->quoteSession->getStoreId();
        return json_encode($this->gatewayConfigProvider->getConfig($storeId));
    }
}