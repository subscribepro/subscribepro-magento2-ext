<?php

namespace Swarming\SubscribePro\Block\Vault\Edit;

class Card extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Swarming\SubscribePro\Gateway\Config\ConfigProvider
     */
    protected $gatewayConfigProvider;

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider,
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        array $data = []
    ) {
        $this->gatewayConfigProvider = $gatewayConfigProvider;
        $this->gatewayConfig = $gatewayConfig;
        $this->pricingHelper = $pricingHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getPaymentConfig()
    {
        return json_encode($this->gatewayConfigProvider->getConfig());
    }

    /**
     * @return bool
     */
    public function isWalletAuthorizationActive()
    {
        return $this->gatewayConfig->isWalletAuthorizationActive();
    }

    /**
     * @return string
     */
    public function getWalletAuthorizationAmount()
    {
        return $this->pricingHelper->currency($this->gatewayConfig->getWalletAuthorizationAmount(), true, false);
    }
}
