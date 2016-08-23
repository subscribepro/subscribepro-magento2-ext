<?php

namespace Swarming\SubscribePro\Block\Vault\Edit;

class EditCard extends \Swarming\SubscribePro\Block\Vault\CardRenderer
{
    /**
     * @return string
     */
    public function getJsLayout()
    {
        $jsLayout = $this->updateJsLayout($this->jsLayout);
        return json_encode($jsLayout);
    }

    /**
     * @param array $jsLayout
     * @return array
     */
    protected function updateJsLayout(array $jsLayout)
    {
        $jsLayout['components']['subscribe-pro-vault-card-edit']['config'] = [
            'creditCardExpMonth' => $this->getExpirationMonth(),
            'creditCardExpYear' => $this->getExpirationYear()
        ];
        return $jsLayout;
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
