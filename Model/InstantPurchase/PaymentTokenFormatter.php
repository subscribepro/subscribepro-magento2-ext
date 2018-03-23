<?php

namespace Swarming\SubscribePro\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Braintree stored credit card formatter.
 */
class PaymentTokenFormatter implements PaymentTokenFormatterInterface
{
    /**
     * Most used credit card types
     * @var array
     */
    public static $baseCardTypes = [
        'AE' => 'American Express',
        'VI' => 'Visa',
        'MC' => 'MasterCard',
        'DI' => 'Discover',
        'JBC' => 'JBC',
        'CUP' => 'China Union Pay',
        'MI' => 'Maestro',
    ];

    /**
     * @inheritdoc
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        if (!isset($details['type'], $details['maskedCC'], $details['expirationDate'])) {
            throw new \InvalidArgumentException('Invalid Subscribe Pro Vault credit card token details.');
        }

        if (isset(self::$baseCardTypes[$details['type']])) {
            $ccType = self::$baseCardTypes[$details['type']];
        } else {
            $ccType = $details['type'];
        }

        $formatted = sprintf(
            '%s %s %s (%s: %s)',
            $ccType,
            __('ending'),
            $details['maskedCC'],
            __('expires'),
            $details['expirationDate']
        );

        return $formatted;
    }
}
