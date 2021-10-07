<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Config;

class ScopeDefiner extends \Magento\Config\Model\Config\ScopeDefiner
{
    /**
     * @return int|null
     */
    public function getScopeValue(): ?int
    {
        $scopeValue = $this->_request->getParam($this->getScope());
        return $scopeValue ? (int)$scopeValue : null;
    }
}
