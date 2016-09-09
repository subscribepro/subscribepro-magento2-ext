<?php

namespace Swarming\SubscribePro\Model;

use Swarming\SubscribePro\Api\Data\AddressInterface;

/**
 * @codeCoverageIgnore
 */
class Address extends \SubscribePro\Service\Address\Address implements AddressInterface
{
    /**
     * @return string|null
     */
    public function getAddressInline()
    {
        return $this->getData(self::ADDRESS_INLINE);
    }

    /**
     * @param string $inline
     * @return $this
     */
    public function setAddressInline($inline)
    {
        return $this->setData(self::ADDRESS_INLINE, $inline);
    }
}
