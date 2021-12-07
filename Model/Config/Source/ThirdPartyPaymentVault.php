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
     * @var \Swarming\SubscribePro\Model\Config\Source\ThirdPartyOptionArray
     */
    private $optionArray;

    /**
     * @param \Swarming\SubscribePro\Model\Config\Source\ThirdPartyOptionArray $optionArray
     * @param array $supportedVaults
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\Source\ThirdPartyOptionArray $optionArray,
        array $supportedVaults = []
    ) {
        $this->optionArray = $optionArray;
        $this->supportedVaults = $supportedVaults;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->optionArray->getOptions($this->supportedVaults);
    }
}
