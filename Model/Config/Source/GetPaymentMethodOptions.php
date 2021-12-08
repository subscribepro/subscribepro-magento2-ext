<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Config\Source;

class GetPaymentMethodOptions
{
    /**
     * @var \Magento\Payment\Api\PaymentMethodListInterface
     */
    private $paymentMethodList;

    /**
     * @var \Swarming\SubscribePro\Service\GetCurrentStoreId
     */
    private $getCurrentStoreId;

    /**
     * @param \Magento\Payment\Api\PaymentMethodListInterface $paymentMethodList
     * @param \Swarming\SubscribePro\Service\GetCurrentStoreId $getCurrentStoreId
     */
    public function __construct(
        \Magento\Payment\Api\PaymentMethodListInterface $paymentMethodList,
        \Swarming\SubscribePro\Service\GetCurrentStoreId $getCurrentStoreId
    ) {
        $this->paymentMethodList = $paymentMethodList;
        $this->getCurrentStoreId = $getCurrentStoreId;
    }

    /**
     * @param string[] $allowedMethodCodes
     * @return array
     */
    public function execute(array $allowedMethodCodes): array
    {
        $options = [];
        $storeId = $this->getCurrentStoreId->execute();

        $paymentMethodList = $this->paymentMethodList->getList($storeId);
        $supportedPaymentMethods = $this->filterSupportedMethods($paymentMethodList, $allowedMethodCodes);
        $duplicatedMethodNames = $this->getDuplicatedMethodNames($supportedPaymentMethods);

        foreach ($supportedPaymentMethods as $method) {
            if ($method->getCode() && $method->getTitle()) {
                $labelParts = [$method->getTitle()];

                if (in_array($method->getTitle(), $duplicatedMethodNames, true)) {
                    $labelParts[] = $method->getCode();
                }

                if (!$method->getIsActive()) {
                    $labelParts[] = __('(disabled)');
                }

                $options[] = ['value' => $method->getCode(), 'label' => implode(' ', $labelParts)];
            }
        }

        return $options;
    }

    /**
     * @param \Magento\Payment\Api\Data\PaymentMethodInterface[] $paymentMethodList
     * @param string[] $allowedMethodCodes
     * @return \Magento\Payment\Api\Data\PaymentMethodInterface[]
     */
    private function filterSupportedMethods(array $paymentMethodList, array $allowedMethodCodes): array
    {
        return array_filter(
            $paymentMethodList,
            static function ($paymentMethod) use ($allowedMethodCodes) {
                return in_array($paymentMethod->getCode(), $allowedMethodCodes, true);
            }
        );
    }

    /**
     * @param \Magento\Payment\Api\Data\PaymentMethodInterface[] $paymentMethodList
     * @return string[]
     */
    private function getDuplicatedMethodNames(array $paymentMethodList): array
    {
        usort(
            $paymentMethodList,
            static function ($comparedObject, $nextObject) {
                return strcmp($comparedObject->getTitle(), $nextObject->getTitle());
            }
        );

        $paymentMethodNames = array_map(
            static function ($paymentMethod) {
                return $paymentMethod->getTitle();
            },
            $paymentMethodList
        );

        return array_unique(array_diff_assoc($paymentMethodNames, array_unique($paymentMethodNames)));
    }
}
