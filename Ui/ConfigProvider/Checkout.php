<?php

namespace Swarming\SubscribePro\Ui\ConfigProvider;

use Magento\Checkout\Model\ConfigProviderInterface;
use Psr\Log\LoggerInterface;
use SubscribePro\Exception\HttpException;
use SubscribePro\Exception\InvalidArgumentException;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider as GatewayConfigProvider;

class Checkout implements ConfigProviderInterface
{
    /**
     * @var GatewayConfigProvider
     */
    protected $gatewayConfigProvider;

    /**
     * @var \Psr\Log\LoggerInterface;
     */
    protected $logger;

    /**
     * @param GatewayConfigProvider $gatewayConfigProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        GatewayConfigProvider    $gatewayConfigProvider,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->gatewayConfigProvider = $gatewayConfigProvider;
        $this->logger = $logger;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        try {
            $config = $this->gatewayConfigProvider->getConfig();
        } catch (InvalidArgumentException|HttpException $e) {
            $this->logger->debug('Cannot load configuration from Subscribe Pro platform.');
            $this->logger->info($e->getMessage());
            $config = [];
        }
        return [
            'payment' => [
                GatewayConfigProvider::CODE => $config,
            ]
        ];
    }
}
