<?php

namespace Swarming\SubscribePro\Service;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Swarming\SubscribePro\Api\PaymentTokenManagementInterface;

class PaymentTokenManagement implements PaymentTokenManagementInterface
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Intl\DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Intl\DateTimeFactory $dateTimeFactory
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * @param int $customerId Customer ID.
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface[]
     */
    public function getSubscribeProTokensByCustomerId($customerId)
    {
        $filters[] = $this->filterBuilder->setField(PaymentTokenInterface::CUSTOMER_ID)
            ->setValue($customerId)
            ->create();
        $filters[] = $this->filterBuilder->setField(PaymentTokenInterface::IS_VISIBLE)
            ->setValue(1)
            ->create();
        $filters[] = $this->filterBuilder->setField(PaymentTokenInterface::IS_ACTIVE)
            ->setValue(1)
            ->create();
        $filters[] = $this->filterBuilder->setField(PaymentTokenInterface::EXPIRES_AT)
            ->setConditionType('gt')
            ->setValue(
                $this->dateTimeFactory->create(
                    'now',
                    new \DateTimeZone('UTC')
                )->format('Y-m-d 00:00:00')
            )
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)
            ->create();

        return $this->paymentTokenRepository->getList($searchCriteria)->getItems();
    }
}
