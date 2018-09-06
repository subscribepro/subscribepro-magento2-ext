<?php

namespace Swarming\SubscribePro\Block\Adminhtml\System\Config;

use SubscribePro\Exception\InvalidArgumentException;

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
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->gatewayConfigProvider = $gatewayConfigProvider;
        $this->quoteSession = $quoteSession;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getPaymentConfig()
    {
        $storeId = $this->quoteSession->getStoreId();

        try {
            $config = $this->gatewayConfigProvider->getConfig($storeId);
        } catch (InvalidArgumentException $e) {
            $config = [];
            $this->logger->debug('Cannog retrieve Subscribe Pro payment config: ' . $e->getMessage());
        }

        return json_encode($config);
    }
}