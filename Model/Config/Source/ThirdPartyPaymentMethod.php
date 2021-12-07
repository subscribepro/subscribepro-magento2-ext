<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Config\Source;

class ThirdPartyPaymentMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    private $supportedMethods;

    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\ThirdPartyOptionArray
     */
    private $optionArray;

    /**
     * @param \Swarming\SubscribePro\Model\Config\Source\ThirdPartyOptionArray $optionArray
     * @param array $supportedMethods
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\Source\ThirdPartyOptionArray $optionArray,
        array $supportedMethods = []
    ) {
        $this->optionArray = $optionArray;
        $this->supportedMethods = $supportedMethods;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->optionArray->getOptions($this->supportedMethods);
    }
}
