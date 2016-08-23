<?php

namespace Swarming\SubscribePro\Ui\ComponentProvider;

use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

class VaultToken implements TokenUiComponentProviderInterface
{
    /**
     * @var \Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory
     */
    protected $componentFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param \Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory $componentFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory $componentFactory,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->componentFactory = $componentFactory;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @return \Magento\Vault\Model\Ui\TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => ConfigProvider::VAULT_CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
                ],
                'name' => 'Swarming_SubscribePro/js/view/payment/method-renderer/vault'
            ]
        );

        return $component;
    }
}
