<?php
namespace Swarming\SubscribePro\Plugin\Payment;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Swarming\SubscribePro\Model\Config\Advanced;
use Magento\Framework\Intl\DateTimeFactory;

class PaymentTokenManagement
{
    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected PaymentTokenRepositoryInterface $paymentTokenRepository;

    /**
     * @var FilterBuilder
     */
    protected FilterBuilder $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var DateTimeFactory
     */
    protected DateTimeFactory $dateTimeFactory;

    /**
     * @var Advanced
     */
    private Advanced $config;

    /**
     * @param PaymentTokenRepositoryInterface $repository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTimeFactory $dateTimeFactory
     * @param Advanced $config
     */
    public function __construct(
        PaymentTokenRepositoryInterface $repository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTimeFactory $dateTimeFactory,
        Advanced $config,
    ) {
        $this->paymentTokenRepository = $repository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->config = $config;
    }

    /**
     * @param \Magento\Vault\Model\PaymentTokenManagement $subject
     * @param callable $proceed
     * @param int $customerId
     * @return PaymentTokenInterface[]
     */
    public function aroundGetVisibleAvailableTokens(
        \Magento\Vault\Model\PaymentTokenManagement $subject,
        callable $proceed,
        int $customerId
    ) {
        $customerFilter = [
            $this->filterBuilder->setField(PaymentTokenInterface::CUSTOMER_ID)
                ->setValue($customerId)
                ->create()
        ];
        $visibleFilter = [
            $this->filterBuilder->setField(PaymentTokenInterface::IS_VISIBLE)
                ->setValue(1)
                ->create()
        ];
        $isActiveFilter = [
            $this->filterBuilder->setField(PaymentTokenInterface::IS_ACTIVE)
                ->setValue(1)
                ->create()
        ];
        $this->searchCriteriaBuilder->addFilters($customerFilter);
        $this->searchCriteriaBuilder->addFilters($visibleFilter);
        $this->searchCriteriaBuilder->addFilters($isActiveFilter);
        if (!$this->config->isExpiredCardsEnabled()) {
            $expiresAtFilter = [
                $this->filterBuilder->setField(PaymentTokenInterface::EXPIRES_AT)
                    ->setConditionType('gt')
                    ->setValue(
                        $this->dateTimeFactory->create(
                            'now',
                            new \DateTimeZone('UTC')
                        )->format('Y-m-d 00:00:00')
                    )
                    ->create()
            ];
            $this->searchCriteriaBuilder->addFilters($expiresAtFilter);
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->paymentTokenRepository->getList($searchCriteria)->getItems();
    }

}
