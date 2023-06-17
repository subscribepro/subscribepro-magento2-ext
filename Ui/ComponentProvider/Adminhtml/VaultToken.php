<?php

namespace Swarming\SubscribePro\Ui\ComponentProvider\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;

class VaultToken implements TokenUiComponentProviderInterface
{
    /**
     * @var \Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory
     */
    protected $componentFactory;

    /**
     * @param \Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory $componentFactory
     */
    public function __construct(
        \Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory $componentFactory
    ) {
        $this->componentFactory = $componentFactory;
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
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    'template' => 'Swarming_SubscribePro::payment/vault.phtml'
                ],
                'name' => Template::class
            ]
        );

        return $component;
    }
}
