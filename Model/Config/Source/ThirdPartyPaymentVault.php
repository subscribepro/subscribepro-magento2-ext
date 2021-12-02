<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Config\Source;

class ThirdPartyPaymentVault implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    private $supportedVaults;

    /**
     * @param array $supportedVaults
     */
    public function __construct(
        array $supportedVaults = []
    ) {
        $this->supportedVaults = $supportedVaults;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->supportedVaults as $vault) {
            $options[] = ['value' => $vault['code'], 'label' => $vault['name']];
        }

        return $options;
    }
}
