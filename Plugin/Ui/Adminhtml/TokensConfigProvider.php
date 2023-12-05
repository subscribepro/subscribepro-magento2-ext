<?php

namespace Swarming\SubscribePro\Plugin\Ui\Adminhtml;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Swarming\SubscribePro\Gateway\Config\ConfigProvider;
use Swarming\SubscribePro\Model\Config\Advanced;
use Swarming\SubscribePro\Ui\ComponentProvider\Adminhtml\VaultToken;

class TokensConfigProvider
{
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private PaymentTokenRepositoryInterface $paymentTokenRepository;

    /**
     * @var FilterBuilder
     */
    private FilterBuilder $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var SessionManagerInterface
     */
    private SessionManagerInterface $session;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var Data
     */
    private Data $paymentDataHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var PaymentTokenManagementInterface
     */
    private PaymentTokenManagementInterface $paymentTokenManagement;

    /**
     * @var Advanced
     */
    private Advanced $config;

    private VaultToken $vaultToken;

    /**
     * Constructor
     *
     * @param SessionManagerInterface $session
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTimeFactory $dateTimeFactory
     * @param Advanced $config
     * @param Data $paymentDataHelper
     * @param VaultToken $vaultToken
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Quote                           $session,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        FilterBuilder                   $filterBuilder,
        SearchCriteriaBuilder           $searchCriteriaBuilder,
        DateTimeFactory                 $dateTimeFactory,
        Advanced                        $config,
        Data                            $paymentDataHelper,
        VaultToken                      $vaultToken,
        PaymentTokenManagementInterface $paymentTokenManagement,
        OrderRepositoryInterface        $orderRepository
    )
    {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->session = $session;
        $this->config = $config;
        $this->paymentDataHelper = $paymentDataHelper;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->vaultToken = $vaultToken;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param \Magento\Vault\Model\Ui\Adminhtml\TokensConfigProvider $subject
     * @param callable $proceed
     * @param string $vaultPaymentCode
     * @return array
     * @throws LocalizedException
     */
    public function aroundGetTokensComponents(
        \Magento\Vault\Model\Ui\Adminhtml\TokensConfigProvider $subject,
        callable                                               $proceed,
        string                                                 $vaultPaymentCode
    )
    {
        if ($vaultPaymentCode !== ConfigProvider::VAULT_CODE) {
            return $proceed($vaultPaymentCode);
        }
        $result = [];
        $customerId = $this->session->getCustomerId();

        $vaultPayment = $this->getVaultPayment($vaultPaymentCode);
        if ($vaultPayment === null) {
            return $result;
        }

        if ($customerId) {
            $this->searchCriteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField(PaymentTokenInterface::CUSTOMER_ID)
                        ->setValue($customerId)
                        ->create(),
                ]
            );
        } else {
            try {
                $this->searchCriteriaBuilder->addFilters(
                    [
                        $this->filterBuilder->setField(PaymentTokenInterface::ENTITY_ID)
                            ->setValue($this->getPaymentTokenEntityId())
                            ->create(),
                    ]
                );
            } catch (InputException|NoSuchEntityException $e) {
                return $result;
            }
        }
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder->setField(PaymentTokenInterface::PAYMENT_METHOD_CODE)
                    ->setValue($vaultPayment->getProviderCode())
                    ->create(),
            ]
        );
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder->setField(PaymentTokenInterface::IS_ACTIVE)
                    ->setValue(1)
                    ->create(),
            ]
        );
        if (!$this->config->isExpiredCardsEnabled()) {
            $this->searchCriteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField(PaymentTokenInterface::EXPIRES_AT)
                        ->setConditionType('gt')
                        ->setValue(
                            $this->dateTimeFactory->create(
                                'now',
                                new \DateTimeZone('UTC')
                            )->format('Y-m-d 00:00:00')
                        )
                        ->create(),
                ]
            );
        }

        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder->setField(PaymentTokenInterface::IS_VISIBLE)
                    ->setValue(1)
                    ->create(),
            ]
        );

        $searchCriteria = $this->searchCriteriaBuilder->create();

        foreach ($this->paymentTokenRepository->getList($searchCriteria)->getItems() as $token) {
            $result[] = $this->vaultToken->getComponentForToken($token);
        }

        return $result;
    }

    /**
     * @param $vaultPaymentCode
     * @return MethodInterface
     * @throws LocalizedException
     */
    private function getVaultPayment($vaultPaymentCode)
    {
        return $this->paymentDataHelper->getMethodInstance($vaultPaymentCode);
    }

    /**
     * @return int|null
     * @throws NoSuchEntityException
     */
    private function getPaymentTokenEntityId()
    {
        $paymentToken = $this->paymentTokenManagement->getByPaymentId($this->getOrderPaymentEntityId());
        if ($paymentToken === null) {
            throw new NoSuchEntityException(
                __('No payment tokens are available for the specified order payment.')
            );
        }
        return $paymentToken->getEntityId();
    }

    /**
     * Returns order payment entity id
     * Using 'getReordered' for Reorder action
     * Using 'getOrder' for Edit action
     *
     * @return int
     */
    private function getOrderPaymentEntityId()
    {
        $orderId = $this->session->getReordered()
            ?: $this->session->getOrder()->getEntityId();
        $order = $this->orderRepository->get($orderId);

        return (int)$order->getPayment()->getEntityId();
    }

}
