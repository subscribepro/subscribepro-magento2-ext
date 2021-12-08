<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Config\Source;

class ThirdPartyPaymentVault implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\GetPaymentMethodOptions
     */
    private $getPaymentMethodOptions;

    /**
     * @var string[]
     */
    private $supportedVaults;

    /**
     * @param \Swarming\SubscribePro\Model\Config\Source\GetPaymentMethodOptions $getPaymentMethodOptions
     * @param string[] $supportedVaults
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\Source\GetPaymentMethodOptions $getPaymentMethodOptions,
        array $supportedVaults = []
    ) {
        $this->getPaymentMethodOptions = $getPaymentMethodOptions;
        $this->supportedVaults = $supportedVaults;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->getPaymentMethodOptions->execute($this->supportedVaults);
    }
}
