<?php

namespace Swarming\SubscribePro\Block\Vault;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Helper\Vault as VaultHelper;

class CardRenderer extends AbstractCardRenderer
{
    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $token
     * @return bool
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === ConfigProvider::CODE;
    }

    /**
     * @return string
     */
    public function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    /**
     * @return string
     */
    public function getExpDate()
    {
        return $this->getTokenDetails()['expirationDate'];
    }

    /**
     * @return string
     */
    public function getIconUrl()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['url'];
    }

    /**
     * @return int
     */
    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    /**
     * @return int
     */
    public function getIconWidth()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        $state = $this->getTokenDetails()['state'] ?? null;
        return $state === VaultHelper::STATE_PENDING;
    }
}
