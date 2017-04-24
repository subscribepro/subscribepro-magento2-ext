<?php

namespace Swarming\SubscribePro\Block\Vault\Edit;

class CardExpiration extends \Swarming\SubscribePro\Block\Vault\CardRenderer
{
    /**
     * @return string
     */
    public function getJsLayout()
    {
        $this->updateJsLayout();
        return parent::getJsLayout();
    }

    /**
     * @return string[]
     */
    protected function updateJsLayout()
    {
        $jsLayout = [
            'components' => [
                'subscribe-pro-vault-card-expiration' => [
                    'config' => [
                        'creditCardExpMonth' => $this->getExpirationMonth(),
                        'creditCardExpYear' => $this->getExpirationYear()
                    ]
                ]
            ]
        ];
        $this->jsLayout = array_merge_recursive($this->jsLayout, $jsLayout);
    }

    /**
     * @return string
     */
    public function getExpirationMonth()
    {
        return $this->getExpiration(0);
    }

    /**
     * @return string
     */
    public function getExpirationYear()
    {
        return $this->getExpiration(1);
    }

    /**
     * @param int $index
     * @return string
     */
    protected function getExpiration($index)
    {
        $expiration = explode('/', $this->getExpDate());
        return !empty($expiration[$index]) ? $expiration[$index] : '';
    }
}
