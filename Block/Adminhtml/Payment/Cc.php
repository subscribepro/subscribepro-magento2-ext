<?php

namespace Swarming\SubscribePro\Block\Adminhtml\Payment;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider;

class Cc extends \Magento\Payment\Block\Form\Cc
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
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        array $data = []
    ) {
        $this->gatewayConfigProvider = $gatewayConfigProvider;
        $this->quoteSession = $quoteSession;
        parent::__construct($context, $paymentConfig, $data);
    }

    /**
     * @return string
     */
    public function getPaymentConfig()
    {
        $storeId = $this->quoteSession->getStoreId();
        return json_encode($this->gatewayConfigProvider->getConfig($storeId));
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return ConfigProvider::CODE;
    }

    /**
     * @return bool
     */
    public function getQuoteHasCustomer()
    {
        return $this->quoteSession->getQuote()->getCustomerId() !== null;
    }
}
