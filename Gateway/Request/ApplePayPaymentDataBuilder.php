<?php
declare(strict_types=1);

namespace Swarming\SubscribePro\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class ApplePayPaymentDataBuilder implements BuilderInterface
{
    public const PAYMENT_METHOD_TOKEN = 'payment_method_token';
    public const PLATFORM_PROFILE_ID = 'payment_profile_id';

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();

        return [
            self::PAYMENT_METHOD_TOKEN => $payment->getAdditionalInformation(self::PAYMENT_METHOD_TOKEN),
            self::PLATFORM_PROFILE_ID => $payment->getAdditionalInformation(self::PLATFORM_PROFILE_ID),
            VaultConfigProvider::IS_ACTIVE_CODE => $payment->getAdditionalInformation(
                VaultConfigProvider::IS_ACTIVE_CODE
            )
        ];
    }
}
