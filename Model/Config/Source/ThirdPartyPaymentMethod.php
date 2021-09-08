<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Model\Config\Source;

class ThirdPartyPaymentMethod implements \Magento\Framework\Data\OptionSourceInterface
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
     * @var string[]
     */
    private $supportedMethods;

    /**
     * @param \Magento\Payment\Api\PaymentMethodListInterface $paymentMethodList
     * @param \Swarming\SubscribePro\Service\GetCurrentStoreId $getCurrentStoreId
     */
    public function __construct(
        \Magento\Payment\Api\PaymentMethodListInterface $paymentMethodList,
        \Swarming\SubscribePro\Service\GetCurrentStoreId $getCurrentStoreId,
        array $supportedMethods = []
    ) {
        $this->paymentMethodList = $paymentMethodList;
        $this->getCurrentStoreId = $getCurrentStoreId;
        $this->supportedMethods = $supportedMethods;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $storeId = $this->getCurrentStoreId->execute();

        $paymentMethodList = $this->paymentMethodList->getList($storeId);
        $supportedPaymentMethods = $this->filterSupportedMethods($paymentMethodList);
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
     * @param array $paymentMethodList
     * @return array
     */
    private function filterSupportedMethods(array $paymentMethodList): array
    {
        return array_filter(
            $paymentMethodList,
            function ($paymentMethod) {
                /** @var \Magento\Payment\Api\Data\PaymentMethodInterface $paymentMethod */
                return in_array($paymentMethod->getCode(), $this->supportedMethods, true);
            }
        );
    }

    /**
     * @param array $paymentMethodList
     * @return array
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
