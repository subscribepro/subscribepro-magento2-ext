<?php

namespace Swarming\SubscribePro\Block\Adminhtml\System\Config;

use Magento\Framework\View\Element\Template;
use SubscribePro\Exception\InvalidArgumentException;
use Swarming\SubscribePro\Gateway\Config\Config as SubscribeProConfig;

class Config extends Template
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
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var SubscribeProConfig
     */
    private $sProConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider
     * @param SubscribeProConfig $sProConfig
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider,
        SubscribeProConfig $sProConfig,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->gatewayConfigProvider = $gatewayConfigProvider;
        $this->quoteSession = $quoteSession;
        $this->logger = $logger;
        $this->sProConfig = $sProConfig;
        parent::__construct($context, $data);
    }

    /**
     * Returns Subscribe Pro payment configs for multiple stores
     *
     * Having the configs for multiple stores is needed for multistore order creation in admin to work correctly
     * Similarily named methods exist that retrn settings for a single store,
     * but their names are in the singular
     * @see \Swarming\SubscribePro\Block\Adminhtml\Payment\Cc
     * @see \Swarming\SubscribePro\Block\Vault\Edit\Card
     *
     * @return string
     */
    public function getPaymentConfigs()
    {
        $config = [];
        $stores = $this->_storeManager->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            if (!$this->sProConfig->isActive($storeId)) {
                continue;
            }
            try {
                $config[$storeId] = $this->gatewayConfigProvider->getConfig($storeId);
            } catch (InvalidArgumentException $e) {
                $config = null;
                $this->logger->debug('Cannot retrieve Subscribe Pro payment config: ' . $e->getMessage());
            }
        }

        return json_encode($config);
    }
}
