<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Block\Cart;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ApplePay extends Template
{
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getMerchantDomainName(): string
    {
        return 'merchant.domain.name';
    }

    public function getApiAccessToken(): string
    {
        return 'api.access.token';
    }

    public function getCreateSessionUrl(): string
    {
        return '';
    }
}
