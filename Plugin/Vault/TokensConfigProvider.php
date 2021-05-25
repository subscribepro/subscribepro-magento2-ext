<?php

namespace Swarming\SubscribePro\Plugin\Vault;

use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Helper\Vault as VaultHelper;

class TokensConfigProvider
{
    /**
     * @param \Magento\Vault\Model\Ui\TokensConfigProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetConfig(\Magento\Vault\Model\Ui\TokensConfigProvider $subject, array $result)
    {
        if (!empty($result['payment']['vault']) && is_array($result['payment']['vault'])) {
            $result['payment']['vault'] = array_filter(
                $result['payment']['vault'],
                static function ($vaultPayment) {
                    $paymentCode = $vaultPayment['config']['code'] ?? '';
                    $paymentState = $vaultPayment['config'][TokenUiComponentProviderInterface::COMPONENT_DETAILS]['state'] ?? '';

                    return $paymentCode !== ConfigProvider::VAULT_CODE || $paymentState !== VaultHelper::STATE_PENDING;
                }
            );
        }
        return $result;
    }
}
