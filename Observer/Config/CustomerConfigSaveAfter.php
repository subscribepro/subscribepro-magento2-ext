<?php

namespace Swarming\SubscribePro\Observer\Config;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Config\Share;

class CustomerConfigSaveAfter implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Config\Model\Config\Factory
     */
    protected $configFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Config\Model\Config\Factory $configFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\Config\Factory $configFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $accountShareScope = $this->scopeConfig->getValue(
            Share::XML_PATH_CUSTOMER_ACCOUNT_SHARE,
            ScopeInterface::SCOPE_STORE
        );
        if ($accountShareScope == Share::SHARE_GLOBAL) {
            return;
        }

        $configData = [
            'section' => 'swarming_subscribepro',
            'website' => null,
            'store' => null,
            'groups' => [
                'platform' => [
                    'fields' => [
                        'client_id' => ['inherit' => true],
                        'client_secret' => ['inherit' => true]
                    ]
                ]
            ],
        ];

        try {
            $configModel = $this->configFactory->create(['data' => $configData]);
            $configModel->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
