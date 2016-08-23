<?php

namespace Swarming\SubscribePro\Block\Vault\Edit;

class CreateCard extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Swarming\SubscribePro\Model\Ui\ConfigProvider
     */
    protected $configProvider;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Swarming\SubscribePro\Model\Ui\ConfigProvider $configProvider
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Swarming\SubscribePro\Model\Ui\ConfigProvider $configProvider,
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getPaymentConfig()
    {
        return $this->configProvider->getPaymentConfig();
    }
}
