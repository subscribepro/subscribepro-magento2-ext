<?php

namespace Swarming\SubscribePro\Gateway\Command;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface
     */
    protected $requestBuilder;

    /**
     * @var \Swarming\SubscribePro\Platform\Platform
     */
    protected $platform;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Swarming\SubscribePro\Gateway\Helper\SubjectReader
     */
    protected $subjectReader;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\PaymentProfile
     */
    protected $platformPaymentProfileService;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\Transaction
     */
    protected $platformTransactionService;

    /**
     * @var \Magento\Payment\Gateway\Response\HandlerInterface
     */
    protected $handler;

    /**
     * @var \Magento\Payment\Gateway\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Payment\Gateway\Response\HandlerInterface $handler
     * @param \Magento\Payment\Gateway\Validator\ValidatorInterface $validator
     * @param \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService
     * @param \Swarming\SubscribePro\Platform\Service\Transaction $platformTransactionService
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Payment\Gateway\Request\BuilderInterface $requestBuilder,
        \Swarming\SubscribePro\Platform\Platform $platform,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Swarming\SubscribePro\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Payment\Gateway\Response\HandlerInterface $handler,
        \Magento\Payment\Gateway\Validator\ValidatorInterface $validator,
        \Swarming\SubscribePro\Platform\Service\PaymentProfile $platformPaymentProfileService,
        \Swarming\SubscribePro\Platform\Service\Transaction $platformTransactionService,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->platform = $platform;
        $this->storeManager = $storeManager;
        $this->subjectReader = $subjectReader;
        $this->handler = $handler;
        $this->validator = $validator;
        $this->platformPaymentProfileService = $platformPaymentProfileService;
        $this->platformTransactionService = $platformTransactionService;
        $this->logger = $logger;
    }

    /**
     * @param array $commandSubject
     * @return TransactionInterface
     * @throws CommandException
     */
    public function execute(array $commandSubject)
    {
        $requestData = $this->requestBuilder->build($commandSubject);

        try {
            $this->setPlatformWebsite($commandSubject);
            $transaction = $this->processTransaction($requestData);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new CommandException(__('Transaction has been declined. Please try again later.'));
        }

        $response = ['transaction' => $transaction];

        $result = $this->validator->validate(array_merge($commandSubject, $response));
        if (!$result->isValid()) {
            throw new CommandException(__('Transaction has been declined. Please try again later.'));
        }

        $this->handler->handle($commandSubject, $response);
        return $transaction;
    }

    /**
     * @param array $commandSubject
     * @return void
     * @throws NoSuchEntityException
     */
    protected function setPlatformWebsite(array $commandSubject)
    {
        $storeId = $commandSubject['store_id'] ?? $this->getStoreIdFromOrder($commandSubject);

        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        $this->platform->setDefaultWebsite($websiteId);
    }

    /**
     * @param array $commandSubject
     * @return int
     */
    protected function getStoreIdFromOrder(array $commandSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($commandSubject);
        return $paymentDO->getOrder()->getStoreId();
    }

    /**
     * @param array $requestData
     * @return TransactionInterface
     */
    abstract protected function processTransaction(array $requestData);
}
