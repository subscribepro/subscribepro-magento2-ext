<?php

namespace Swarming\SubscribePro\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface;

/**
 * Availability of Subscribe Pro Vault for instant purchase.
 */
class AvailabilityChecker implements AvailabilityCheckerInterface
{
    /**
     * @inheritdoc
     */
    public function isAvailable(): bool
    {
        return true;
    }
}
