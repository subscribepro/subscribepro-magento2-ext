<?php

namespace Swarming\SubscribePro\Api\Data;

interface AddressInterface extends \SubscribePro\Service\Address\AddressInterface
{
    const ADDRESS_INLINE = 'address_inline';

    /**
     * @return string|null
     */
    public function getAddressInline();

    /**
     * @param string $inline
     * @return $this
     */
    public function setAddressInline($inline);
}
