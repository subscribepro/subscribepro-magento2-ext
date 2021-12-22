<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Config\Source;

class ThirdPartyPaymentMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\Source\GetPaymentMethodOptions
     */
    private $getPaymentMethodOptions;

    /**
     * @var string[]
     */
    private $supportedMethods;

    /**
     * @param \Swarming\SubscribePro\Model\Config\Source\GetPaymentMethodOptions $getPaymentMethodOptions
     * @param string[] $supportedMethods
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\Source\GetPaymentMethodOptions $getPaymentMethodOptions,
        array $supportedMethods = []
    ) {
        $this->getPaymentMethodOptions = $getPaymentMethodOptions;
        $this->supportedMethods = $supportedMethods;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->getPaymentMethodOptions->execute($this->supportedMethods);
    }
}
