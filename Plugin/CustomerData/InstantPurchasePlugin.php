<?php

declare(strict_types=1);

namespace Swarming\SubscribePro\Plugin\CustomerData;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InstantPurchase\CustomerData\InstantPurchase;
use Magento\InstantPurchase\PaymentMethodIntegration\Integration;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Swarming\SubscribePro\Gateway\Config\Config;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\InstantPurchase\PaymentMethodIntegration\IntegrationsManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\InstantPurchase\Model\Ui\PaymentTokenFormatter;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;

class InstantPurchasePlugin
{
    /**
     * @var Config
     */
    private Config $gatewayConfig;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var SortOrderBuilder
     */
    private SortOrderBuilder $sortOrderBuilder;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var IntegrationsManager
     */
    private IntegrationsManager $integrationsManager;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private PaymentTokenRepositoryInterface $paymentTokenRepository;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var PaymentTokenFormatter
     */
    private PaymentTokenFormatter $paymentTokenFormatter;

    /**
     * @param Config $gatewayConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTimeFactory $dateTimeFactory
     * @param SortOrderBuilder $sortOrderBuilder
     * @param IntegrationsManager $integrationsManager
     * @param StoreManagerInterface $storeManager
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param Session $customerSession
     * @param PaymentTokenFormatter $paymentTokenFormatter
     */
    public function __construct(
        Config $gatewayConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTimeFactory $dateTimeFactory,
        SortOrderBuilder $sortOrderBuilder,
        IntegrationsManager $integrationsManager,
        StoreManagerInterface $storeManager,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        Session $customerSession,
        PaymentTokenFormatter $paymentTokenFormatter
    ) {
        $this->gatewayConfig = $gatewayConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->integrationsManager = $integrationsManager;
        $this->storeManager = $storeManager;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->customerSession = $customerSession;
        $this->paymentTokenFormatter = $paymentTokenFormatter;
    }

    /**
     * @param InstantPurchase $subject
     * @param $result
     * @return array[]|mixed
     * @throws NoSuchEntityException
     */
    public function afterGetSectionData(InstantPurchase $subject, $result): mixed
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $isNonSpTransactionActive = $this->gatewayConfig->isNonSubscriptionTransactionActive($storeId);
        $isSpMethodActive = $this->gatewayConfig->isActive($storeId);
        $result['isNonSubscriptionTransactionActive'] = $isNonSpTransactionActive;
        if (!$isNonSpTransactionActive) {
            $customerId = (int)$this->customerSession->getCustomerId();
            if ($isSpMethodActive) {
                $spPaymentMethodToken = $this->getPaymentMethodToken($storeId, $customerId, true);
                if ($spPaymentMethodToken) {
                    $result = $this->prepareResult('spPaymentToken', array_shift($spPaymentMethodToken), $result);
                }
            }
            $nonSpPaymentMethodToken = $this->getPaymentMethodToken($storeId, $customerId, false);
            if ($nonSpPaymentMethodToken) {
                $result = $this->prepareResult('nonSpPaymentToken', array_shift($nonSpPaymentMethodToken), $result);
            }
        }
        return $result;
    }

    /**
     * @param string $paymentTokenType
     * @param PaymentTokenInterface $paymentToken
     * @param array $result
     * @return array[]
     */
    private function prepareResult(string $paymentTokenType, PaymentTokenInterface $paymentToken, array $result): array
    {
        return $result + [
                $paymentTokenType => [
                    'publicHash' => $paymentToken->getPublicHash(),
                    'summary' => $this->paymentTokenFormatter->format($paymentToken),
                ]
            ];
    }

    /**
     * @param int $storeId
     * @param int|null $customerId
     * @param bool $useSpMethod
     * @return PaymentTokenInterface[]
     */
    private function getPaymentMethodToken(int $storeId, int|null $customerId, bool $useSpMethod = false): array
    {
        $searchCriteria = $this->buildSearchCriteria($storeId, $customerId, $useSpMethod);
        $searchResult = $this->paymentTokenRepository->getList($searchCriteria);
        return $searchResult->getItems();
    }

    /**
     * @param int $storeId
     * @param int|null $customerId
     * @param bool $useSpMethod
     * @return SearchCriteriaInterface
     */
    private function buildSearchCriteria(int $storeId, int|null $customerId, bool $useSpMethod = false): SearchCriteriaInterface
    {
        $this->searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::CUSTOMER_ID,
            $customerId
        );
        $this->searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::IS_VISIBLE,
            1
        );
        $this->searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::IS_ACTIVE,
            1
        );
        $this->searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::PAYMENT_METHOD_CODE,
            $this->getSupportedPaymentMethodCodes($storeId),
            'in'
        );
        if ($useSpMethod) {
            $this->searchCriteriaBuilder->addFilter(
                PaymentTokenInterface::PAYMENT_METHOD_CODE,
                ConfigProvider::CODE,
                'eq'
            );
        }
        if (!$useSpMethod) {
            $this->searchCriteriaBuilder->addFilter(
                PaymentTokenInterface::PAYMENT_METHOD_CODE,
                ConfigProvider::CODE,
                'neq'
            );
        }
        $this->searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::EXPIRES_AT,
            $this->dateTimeFactory->create('now', new \DateTimeZone('UTC'))
                ->format('Y-m-d 00:00:00'),
            'gt'
        );

        $creationReverseOrder = $this->sortOrderBuilder->setField(PaymentTokenInterface::CREATED_AT)
            ->setDescendingDirection()
            ->create();
        $this->searchCriteriaBuilder->addSortOrder($creationReverseOrder);
        $this->searchCriteriaBuilder->setPageSize(1);

        return $this->searchCriteriaBuilder->create();
    }

    /**
     * Lists supported payment method codes.
     *
     * @param int $storeId
     * @return array
     */
    private function getSupportedPaymentMethodCodes(int $storeId): array
    {
        $integrations = $this->integrationsManager->getList($storeId);
        $integrations = array_filter($integrations, function (Integration $integration) {
            return $integration->isAvailable();
        });
        return array_map(function (Integration $integration) {
            return $integration->getVaultProviderCode();
        }, $integrations);
    }
}
